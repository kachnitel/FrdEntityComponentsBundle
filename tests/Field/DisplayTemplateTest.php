<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestOwnerEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestTagEntity;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests that `templates/components/field/_display.html.twig` correctly renders
 * every possible `value` type without throwing an exception.
 *
 * Covered scenarios:
 *   - null
 *   - bool true / false
 *   - int, float
 *   - string (empty and non-empty)
 *   - BackedEnum              → enum .value string
 *   - UnitEnum                → enum .name string
 *   - iterable / array        → joined values
 *   - object with __toString()         → __toString() result
 *   - object with name getter          → getName() result
 *   - object with label getter         → getLabel() result
 *   - object with title getter         → getTitle() result
 *   - object with only id getter       → "#ID" fallback
 *   - Doctrine proxy after em->clear() → no toString crash
 *
 * Note: an object with *no* .id, *no* recognised enum properties, and *no*
 * __toString cannot be safely rendered by Twig without a custom extension.
 * Such objects are not valid field values in this bundle's domain and are
 * intentionally not covered.
 *
 * @group field
 * @group field-display-template
 */
#[CoversNothing]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
class DisplayTemplateTest extends FieldTestCase
{
    private function renderDisplay(mixed $value): string
    {
        /** @var \Twig\Environment $twig */
        $twig = static::getContainer()->get('twig');

        return trim($twig->render(
            '@KachnitelEntityComponents/components/field/_display.html.twig',
            ['value' => $value],
        ));
    }

    // ── null ──────────────────────────────────────────────────────────────────

    public function testNullRendersEmDashInsideTextMutedSpan(): void
    {
        $html = $this->renderDisplay(null);

        $this->assertStringContainsString('—', $html);
        $this->assertStringContainsString('text-muted', $html);
    }

    // ── bool ──────────────────────────────────────────────────────────────────

    public function testTrueRendersYes(): void
    {
        $this->assertStringContainsString('Yes', $this->renderDisplay(true));
    }

    public function testFalseRendersNo(): void
    {
        $this->assertStringContainsString('No', $this->renderDisplay(false));
    }

    public function testTrueAndFalseRenderDifferently(): void
    {
        $this->assertNotSame(
            $this->renderDisplay(true),
            $this->renderDisplay(false),
        );
    }

    // ── scalars ───────────────────────────────────────────────────────────────

    public function testIntegerRendersNumericString(): void
    {
        $this->assertStringContainsString('42', $this->renderDisplay(42));
    }

    public function testFloatRendersDecimalString(): void
    {
        $this->assertStringContainsString('3.14', $this->renderDisplay(3.14));
    }

    public function testNonEmptyStringRendersValue(): void
    {
        $this->assertStringContainsString('Hello World', $this->renderDisplay('Hello World'));
    }

    public function testEmptyStringRendersWithoutError(): void
    {
        $this->assertIsString($this->renderDisplay(''));
    }

    // ── iterable ─────────────────────────────────────────────────────────────

    public function testArrayRendersJoinedValues(): void
    {
        $html = $this->renderDisplay(['foo', 'bar', 'baz']);
        $this->assertStringContainsString('foo', $html);
        $this->assertStringContainsString('bar', $html);
        $this->assertStringContainsString('baz', $html);
    }

    public function testEmptyArrayRendersWithoutError(): void
    {
        $this->assertIsString($this->renderDisplay([]));
    }

    // ── enums ─────────────────────────────────────────────────────────────────

    public function testBackedEnumRendersEnumValue(): void
    {
        $html = $this->renderDisplay(DisplayTestBackedStatus::Active);
        $this->assertStringContainsString('active', $html);
    }

    public function testUnitEnumRendersEnumName(): void
    {
        $html = $this->renderDisplay(DisplayTestUnitStatus::Pending);
        $this->assertStringContainsString('Pending', $html);
    }

    // ── entity objects (the bug scenario) ─────────────────────────────────────

    /**
     * A related entity with __toString() — highest priority display path.
     * This is the exact crash case from the bug report.
     */
    public function testEntityWithToStringUsesToString(): void
    {
        $tag = new FieldTestTagEntity('Electronics');
        $this->em->persist($tag);
        $this->em->flush();

        $this->assertStringContainsString('Electronics', $this->renderDisplay($tag));
    }

    public function testEntityWithNameGetterRendersName(): void
    {
        $this->assertStringContainsString(
            'Display Name',
            $this->renderDisplay(new DisplayTestEntityWithName('Display Name')),
        );
    }

    public function testEntityWithLabelGetterRendersLabel(): void
    {
        $this->assertStringContainsString(
            'My Label',
            $this->renderDisplay(new DisplayTestEntityWithLabel('My Label')),
        );
    }

    public function testEntityWithTitleGetterRendersTitle(): void
    {
        $this->assertStringContainsString(
            'My Title',
            $this->renderDisplay(new DisplayTestEntityWithTitle('My Title')),
        );
    }

    public function testEntityWithOnlyIdRendersHashIdFallback(): void
    {
        $html = $this->renderDisplay(new DisplayTestEntityWithIdOnly(99));

        $this->assertStringContainsString('#', $html);
        $this->assertStringContainsString('99', $html);
    }

    /**
     * A Doctrine proxy (lazy-loaded ManyToOne) must not trigger
     * "Object of class Proxies\\__CG__\\... could not be converted to string".
     */
    public function testDoctrineProxyDoesNotCrashWithStringConversionError(): void
    {
        $tag = new FieldTestTagEntity('Proxy Tag');
        $this->em->persist($tag);

        $owner = new FieldTestOwnerEntity('Post');
        $owner->setPrimaryTag($tag);
        $this->em->persist($owner);
        $this->em->flush();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestOwnerEntity::class, $owner->getId());
        $this->assertNotNull($reloaded);

        $related = $reloaded->getPrimaryTag();
        $this->assertNotNull($related);

        $html = $this->renderDisplay($related);

        $this->assertStringNotContainsString('Proxies\\__CG__', $html);
        $this->assertStringContainsString('Proxy Tag', $html);
    }

    // ── no-throw data provider covering full matrix ───────────────────────────

    /**
     * Smoke-test: none of these values should throw.
     *
     * @return iterable<string, array{0: mixed}>
     */
    public static function noThrowProvider(): iterable
    {
        yield 'null'            => [null];
        yield 'true'            => [true];
        yield 'false'           => [false];
        yield 'int zero'        => [0];
        yield 'int positive'    => [42];
        yield 'int negative'    => [-7];
        yield 'float'           => [1.5];
        yield 'empty string'    => [''];
        yield 'string'          => ['hello'];
        yield 'empty array'     => [[]];
        yield 'string array'    => [['a', 'b']];
        yield 'backed enum'     => [DisplayTestBackedStatus::Active];
        yield 'unit enum'       => [DisplayTestUnitStatus::Pending];
        yield 'obj name'        => [new DisplayTestEntityWithName('x')];
        yield 'obj label'       => [new DisplayTestEntityWithLabel('x')];
        yield 'obj title'       => [new DisplayTestEntityWithTitle('x')];
        yield 'obj id only'     => [new DisplayTestEntityWithIdOnly(1)];
    }

    #[DataProvider('noThrowProvider')]
    public function testValueDoesNotThrow(mixed $value): void
    {
        $this->assertIsString($this->renderDisplay($value));
    }
}

// ── Local fixtures ────────────────────────────────────────────────────────────

enum DisplayTestBackedStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}

enum DisplayTestUnitStatus
{
    case Pending;
    case Done;
}

class DisplayTestEntityWithName
{
    public function __construct(private readonly string $name) {}
    public function getId(): int { return 1; }
    public function getName(): string { return $this->name; }
}

class DisplayTestEntityWithLabel
{
    public function __construct(private readonly string $label) {}
    public function getId(): int { return 2; }
    public function getLabel(): string { return $this->label; }
}

class DisplayTestEntityWithTitle
{
    public function __construct(private readonly string $title) {}
    public function getId(): int { return 3; }
    public function getTitle(): string { return $this->title; }
}

class DisplayTestEntityWithIdOnly
{
    public function __construct(private readonly int $id) {}
    public function getId(): int { return $this->id; }
}
