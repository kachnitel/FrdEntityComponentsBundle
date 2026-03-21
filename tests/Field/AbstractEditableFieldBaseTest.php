<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\StringField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests for AbstractEditableField base-class behaviour that applies uniformly
 * to every concrete field subclass. Uses StringField as the simplest vehicle.
 *
 * Covers:
 *  - mount() contract enforcement (InvalidArgumentException on bad entities)
 *  - canEdit() early-return guards (before the EditabilityResolver is consulted)
 *  - getLabel() camelCase → "Title Case Words" conversion
 *  - getEntityShortClass() FQCN → short name extraction
 */
#[CoversClass(StringField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-base')]
class AbstractEditableFieldBaseTest extends FieldTestCase
{
    private function getComponent(): StringField
    {
        return $this->getFieldComponent(StringField::class);
    }

    private function persistedEntity(string $name = 'test'): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setName($name);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    // ── mount() contract ──────────────────────────────────────────────────────

    public function testMountThrowsWhenEntityHasNoGetIdMethod(): void
    {
        $entity    = new class {};
        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have a getId() method');

        $component->mount($entity, 'name');
    }

    public function testMountThrowsWhenGetIdReturnsString(): void
    {
        $entity = new class {
            public function getId(): string { return 'uuid-here'; }
        };

        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must return int');

        $component->mount($entity, 'name');
    }

    public function testMountThrowsWhenGetIdReturnsNull(): void
    {
        $entity = new class {
            /** @phpstan-ignore return.unusedType */
            public function getId(): ?int { return null; }
        };

        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must return int');

        $component->mount($entity, 'name');
    }

    public function testMountSetsAllLiveProps(): void
    {
        $entity    = $this->persistedEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'name');

        $this->assertSame(FieldTestEntity::class, $component->entityClass);
        $this->assertSame($entity->getId(), $component->entityId);
        $this->assertSame('name', $component->property);
        $this->assertSame($entity, $component->resolvedEntity);
    }

    // ── canEdit() early-return guards ─────────────────────────────────────────

    /**
     * Default state before mount(): entityClass='' → false immediately,
     * without consulting the EditabilityResolver or calling getEntity().
     */
    public function testCanEditReturnsFalseWhenEntityClassIsEmpty(): void
    {
        $component = $this->getComponent();

        $this->assertFalse($component->canEdit());
    }

    /**
     * entityId=0 is the default before mount(). The guard catches this so
     * the component never calls find(entityClass, 0) against the database.
     */
    public function testCanEditReturnsFalseWhenEntityIdIsZero(): void
    {
        $component              = $this->getComponent();
        $component->entityClass = FieldTestEntity::class;
        // entityId defaults to 0

        $this->assertFalse($component->canEdit());
    }

    public function testActivateEditingIsNoopWhenCanEditReturnsFalse(): void
    {
        $component = $this->getComponent();

        $component->activateEditing();

        $this->assertFalse($component->editMode, 'activateEditing must not set editMode when canEdit() is false');
    }

    // ── getLabel() ────────────────────────────────────────────────────────────

    public function testGetLabelCapitalizesSimpleName(): void
    {
        $component           = $this->getComponent();
        $component->property = 'name';

        $this->assertSame('Name', $component->getLabel());
    }

    public function testGetLabelSplitsCamelCaseIntoWords(): void
    {
        $component           = $this->getComponent();
        $component->property = 'createdAt';

        $this->assertSame('Created At', $component->getLabel());
    }

    public function testGetLabelHandlesMultiWordProperty(): void
    {
        $component           = $this->getComponent();
        $component->property = 'firstName';

        $this->assertSame('First Name', $component->getLabel());
    }

    public function testGetLabelWorksAfterMount(): void
    {
        $entity    = $this->persistedEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'createdAt');

        $this->assertSame('Created At', $component->getLabel());
    }

    // ── getEntityShortClass() ─────────────────────────────────────────────────

    public function testGetEntityShortClassExtractsNameFromFqcn(): void
    {
        $entity    = $this->persistedEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'name');

        $this->assertSame('FieldTestEntity', $component->getEntityShortClass());
    }

    public function testGetEntityShortClassReturnsEmptyStringBeforeMount(): void
    {
        $component = $this->getComponent();

        $this->assertSame('', $component->getEntityShortClass());
    }

    public function testGetEntityShortClassWorksForDeepNamespace(): void
    {
        $component              = $this->getComponent();
        $component->entityClass = 'App\\Domain\\Order\\Entity\\LineItem';

        $this->assertSame('LineItem', $component->getEntityShortClass());
    }
}
