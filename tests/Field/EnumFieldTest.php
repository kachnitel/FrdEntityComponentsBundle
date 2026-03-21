<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\EnumField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(EnumField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-enum')]
class EnumFieldTest extends FieldTestCase
{
    private function createEntity(?FieldTestStatus $status = FieldTestStatus::Active): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setStatus($status);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): EnumField
    {
        return $this->getFieldComponent(EnumField::class);
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountInitializesSelectedValueFromBackedEnum(): void
    {
        $entity    = $this->createEntity(FieldTestStatus::Active);
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $this->assertSame('active', $component->selectedValue);
    }

    public function testMountDoesNotReadValueWhenNotInEditMode(): void
    {
        $entity    = $this->createEntity(FieldTestStatus::Archived);
        $component = $this->getComponent();
        $component->editMode = false;
        $component->mount($entity, 'status');

        $this->assertNull($component->selectedValue);
    }

    public function testMountInitializesNullWhenEntityValueIsNull(): void
    {
        $entity    = $this->createEntity(null);
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $this->assertNull($component->selectedValue);
    }

    // ── canEdit() ─────────────────────────────────────────────────────────────

    public function testCanEditReturnsTrueForWritableProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');

        $this->assertTrue($component->canEdit());
    }

    // ── getEnumCases() ────────────────────────────────────────────────────────

    public function testGetEnumCasesReturnsAllCases(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');

        $cases = $component->getEnumCases();

        $this->assertCount(3, $cases);
        $this->assertArrayHasKey('active', $cases);
        $this->assertArrayHasKey('inactive', $cases);
        $this->assertArrayHasKey('archived', $cases);
    }

    public function testGetEnumCasesHumanizesLabels(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');

        $cases = $component->getEnumCases();
        $this->assertSame('Active', $cases['active']);
        $this->assertSame('Inactive', $cases['inactive']);
        $this->assertSame('Archived', $cases['archived']);
    }

    public function testGetEnumCasesReturnsEmptyForNonEnumProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'name');

        $this->assertSame([], $component->getEnumCases());
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSavePersistsBackedEnum(): void
    {
        $entity    = $this->createEntity(FieldTestStatus::Active);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $component->selectedValue = FieldTestStatus::Archived->value;
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestEntity::class, $id);
        $this->assertSame(FieldTestStatus::Archived, $reloaded?->getStatus());
    }

    public function testSaveSetsNullWhenValueIsEmpty(): void
    {
        $entity    = $this->createEntity(FieldTestStatus::Active);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $component->selectedValue = null;
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestEntity::class, $id);
        $this->assertNull($reloaded?->getStatus());
    }

    public function testSaveExitsEditModeAndSetsSuccessFlag(): void
    {
        $entity    = $this->createEntity(FieldTestStatus::Active);
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $component->selectedValue = FieldTestStatus::Inactive->value;
        $component->save();

        $this->assertFalse($component->editMode);
        $this->assertTrue($component->saveSuccess);
    }

    public function testSaveThrowsForNonEnumProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'name');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid enum type');

        $component->save();
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditClearsSelectedValue(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'status');

        $component->selectedValue = 'archived';
        $component->cancelEdit();

        $this->assertNull($component->selectedValue);
        $this->assertFalse($component->editMode);
    }

    public function testCancelEditClearsErrorMessage(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'old error';
        $component->mount($entity, 'status');

        $component->cancelEdit();

        $this->assertSame('', $component->errorMessage);
    }

    // ── getFormFieldConfig() ──────────────────────────────────────────────────

    public function testGetFormFieldConfigReturnsChoiceType(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');

        $config = $component->getFormFieldConfig();
        $this->assertSame('choice', $config['type']);
        $this->assertIsArray($config['choices']);
        $this->assertCount(3, $config['choices']);
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
