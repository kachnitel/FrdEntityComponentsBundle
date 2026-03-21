<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\EnumField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestStatus;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\EnumField
 * @group field
 * @group field-enum
 */
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

    // ── getFormFieldConfig() ──────────────────────────────────────────────────

    public function testGetFormFieldConfigReturnsChoiceType(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'status');

        $config = $component->getFormFieldConfig();
        $this->assertSame('choice', $config['type']);
        $this->assertIsArray($config['choices']);
    }
}
