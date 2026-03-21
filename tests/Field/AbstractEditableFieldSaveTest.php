<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\FloatField;
use Kachnitel\EntityComponentsBundle\Components\Field\StringField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestValidatableEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests for the save() lifecycle in AbstractEditableField:
 *
 *  1. canEdit() guard — save() throws RuntimeException before any entity mutation when
 *     the EditabilityResolver denies access.
 *  2. Validation integration — when a Symfony constraint is violated, errorMessage is set,
 *     editMode stays true, and no flush occurs. Entity is refreshed from DB.
 *  3. Save success — after a valid save, saveSuccess=true, editMode=false, errorMessage=''.
 *  4. activateEditing() — clears stale errorMessage and saveSuccess before a new edit session.
 *
 * Uses FieldTestValidatableEntity which carries #[Assert\NotBlank], #[Assert\Length],
 * and #[Assert\Range] constraints directly on the entity.
 */
#[CoversClass(StringField::class)]
#[CoversClass(FloatField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-validation')]
#[Group('field-save')]
class AbstractEditableFieldSaveTest extends FieldTestCase
{
    private function createValidatableEntity(string $title = 'Valid Title', float $score = 50.0): FieldTestValidatableEntity
    {
        $entity = (new FieldTestValidatableEntity())
            ->setTitle($title)
            ->setScore($score);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    // ── canEdit() guard ────────────────────────────────────────────────────────

    /**
     * A property with no setter must be rejected before any mutation.
     * DefaultEditabilityResolver delegates to PropertyAccessor::isWritable().
     */
    public function testSaveThrowsAccessDeniedForReadOnlyProperty(): void
    {
        $entity = $this->createValidatableEntity();

        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'id'); // 'id' has no setter

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Access denied');

        $component->save();
    }

    // ── Validation: StringField / NotBlank ────────────────────────────────────

    public function testSaveWithBlankTitleSetsErrorMessage(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = '';

        $component->save();

        $this->assertNotSame('', $component->errorMessage, 'errorMessage must be set when NotBlank is violated');
    }

    public function testSaveWithTooLongTitleSetsErrorMessage(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = str_repeat('x', 25); // exceeds max: 20

        $component->save();

        $this->assertNotSame('', $component->errorMessage);
    }

    public function testValidationFailureKeepsEditModeTrue(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = '';

        $component->save();

        $this->assertTrue($component->editMode, 'editMode must stay true after failed validation');
    }

    public function testValidationFailureDoesNotSetSuccessFlag(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = '';

        $component->save();

        $this->assertFalse($component->saveSuccess);
    }

    /**
     * After a failed validation the entity must be refreshed from the DB so the
     * in-memory value is rolled back. No flush should occur.
     */
    public function testValidationFailureDoesNotPersistInvalidValue(): void
    {
        $entity = $this->createValidatableEntity('Original');
        $id     = $entity->getId();

        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = str_repeat('x', 25);

        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestValidatableEntity::class, $id);
        $this->assertSame('Original', $reloaded?->getTitle(), 'DB value must not change after failed validation');
    }

    // ── Validation: FloatField / Range ────────────────────────────────────────

    public function testSaveWithOutOfRangeScoreSetsErrorMessage(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(FloatField::class);
        $component->editMode = true;
        $component->mount($entity, 'score');
        $component->currentValue = 150.0; // exceeds max: 100

        $component->save();

        $this->assertNotSame('', $component->errorMessage);
        $this->assertTrue($component->editMode);
    }

    public function testSaveWithNegativeScoreSetsErrorMessage(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(FloatField::class);
        $component->editMode = true;
        $component->mount($entity, 'score');
        $component->currentValue = -1.0; // below min: 0

        $component->save();

        $this->assertNotSame('', $component->errorMessage);
    }

    // ── Successful save ────────────────────────────────────────────────────────

    public function testValidSaveClearsErrorAndSetsSuccessFlag(): void
    {
        $entity    = $this->createValidatableEntity('Old');
        $component = $this->getFieldComponent(StringField::class);
        $component->editMode     = true;
        $component->errorMessage = 'Previous error'; // simulate prior failure
        $component->mount($entity, 'title');
        $component->currentValue = 'New';

        $component->save();

        $this->assertSame('', $component->errorMessage);
        $this->assertTrue($component->saveSuccess);
        $this->assertFalse($component->editMode);
    }

    public function testValidSavePersistsToDB(): void
    {
        $entity = $this->createValidatableEntity('Old Title');
        $id     = $entity->getId();

        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = 'New Title';

        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestValidatableEntity::class, $id);
        $this->assertSame('New Title', $reloaded?->getTitle());
    }

    public function testValidFloatSavePersistsToDB(): void
    {
        $entity = $this->createValidatableEntity('T', 50.0);
        $id     = $entity->getId();

        $component = $this->getFieldComponent(FloatField::class);
        $component->editMode = true;
        $component->mount($entity, 'score');
        $component->currentValue = 75.5;

        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestValidatableEntity::class, $id);
        $this->assertEqualsWithDelta(75.5, $reloaded?->getScore(), 0.0001);
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->mount($entity, 'title');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }

    public function testActivateEditingIsNoopWhenCanEditReturnsFalse(): void
    {
        $entity    = $this->createValidatableEntity();
        $component = $this->getFieldComponent(StringField::class);
        $component->mount($entity, 'id'); // read-only
        $component->editMode = false;

        $component->activateEditing();

        $this->assertFalse($component->editMode, 'editMode must not be set when canEdit() is false');
    }

    // ── Multiple consecutive saves ────────────────────────────────────────────

    public function testSuccessiveSavesWorkCorrectly(): void
    {
        $entity = $this->createValidatableEntity('First');

        $component = $this->getFieldComponent(StringField::class);
        $component->editMode = true;
        $component->mount($entity, 'title');
        $component->currentValue = 'Second';
        $component->save();

        $this->assertTrue($component->saveSuccess);

        $component->activateEditing();
        $this->assertFalse($component->saveSuccess);
        $this->assertSame('', $component->errorMessage);
        $this->assertTrue($component->editMode);

        $component->currentValue = 'Third';
        $component->save();

        $this->assertSame('', $component->errorMessage);
        $this->assertTrue($component->saveSuccess);
    }
}
