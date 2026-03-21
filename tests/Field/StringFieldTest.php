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

#[CoversClass(StringField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
class StringFieldTest extends FieldTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createEntity(string $name = 'Original'): FieldTestEntity
    {
        $entity = new FieldTestEntity();
        $entity->setName($name);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): StringField
    {
        return $this->getFieldComponent(StringField::class);
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountPopulatesCurrentValueInEditMode(): void
    {
        $entity    = $this->createEntity('Hello');
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $this->assertSame('Hello', $component->currentValue);
    }

    public function testMountSetsNullWhenEntityValueIsNull(): void
    {
        $entity = new FieldTestEntity();
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $this->assertNull($component->currentValue);
    }

    public function testMountDoesNotReadValueWhenNotInEditMode(): void
    {
        $entity    = $this->createEntity('Ignored');
        $component = $this->getComponent();
        $component->editMode = false;
        $component->mount($entity, 'name');

        $this->assertNull($component->currentValue);
    }

    // ── canEdit() — DefaultEditabilityResolver ────────────────────────────────

    public function testCanEditReturnsTrueForWritableProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'name');

        $this->assertTrue($component->canEdit());
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSavePersistsNewValue(): void
    {
        $entity    = $this->createEntity('Before');
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->currentValue = 'After';
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestEntity::class, $id);
        $this->assertSame('After', $reloaded?->getName());
    }

    public function testSaveExitsEditMode(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->currentValue = 'New';
        $component->save();

        $this->assertFalse($component->editMode);
    }

    public function testSaveSetsSuccessFlag(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->currentValue = 'New';
        $component->save();

        $this->assertTrue($component->saveSuccess);
    }

    public function testSaveClearsErrorMessage(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'Old error';
        $component->mount($entity, 'name');

        $component->currentValue = 'Valid';
        $component->save();

        $this->assertSame('', $component->errorMessage);
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditExitsEditMode(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->cancelEdit();

        $this->assertFalse($component->editMode);
    }

    public function testCancelEditRevertsCurrentValueToPersistedValue(): void
    {
        $entity    = $this->createEntity('Persisted');
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->currentValue = 'Unsaved Draft';
        $component->cancelEdit();

        $this->assertSame('Persisted', $component->currentValue);
    }

    public function testCancelEditDoesNotPersistUnsavedInput(): void
    {
        $entity = $this->createEntity('Original');
        $id     = $entity->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $component->currentValue = 'Should Not Save';
        $component->cancelEdit();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestEntity::class, $id);
        $this->assertSame('Original', $reloaded?->getName());
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearesFeedbackState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'name');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
