<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\BoolField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\BoolField
 * @group field
 * @group field-bool
 */
class BoolFieldTest extends FieldTestCase
{
    private function createEntity(?bool $active = false): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setActive($active);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): BoolField
    {
        return $this->getFieldComponent(BoolField::class);
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountHydratesTrueValue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(true), 'active');

        $this->assertTrue($component->currentValue);
    }

    public function testMountHydratesFalseValue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(false), 'active');

        $this->assertFalse($component->currentValue);
    }

    /**
     * BoolField coerces null → false. Nullable boolean columns are not supported
     * by this field component; document this as a known limitation.
     */
    public function testMountCoercesNullToFalse(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(null), 'active');

        $this->assertFalse($component->currentValue);
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSavePersistsTrue(): void
    {
        $entity    = $this->createEntity(false);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'active');

        $component->currentValue = true;
        $component->save();

        $this->em->clear();
        $this->assertTrue($this->em->find(FieldTestEntity::class, $id)?->getActive());
    }

    public function testSavePersistsFalse(): void
    {
        $entity    = $this->createEntity(true);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'active');

        $component->currentValue = false;
        $component->save();

        $this->em->clear();
        $this->assertFalse($this->em->find(FieldTestEntity::class, $id)?->getActive());
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditRevertsToTrue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(true), 'active');

        $component->currentValue = false;
        $component->cancelEdit();

        $this->assertTrue($component->currentValue);
    }

    public function testCancelEditRevertsToFalse(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(false), 'active');

        $component->currentValue = true;
        $component->cancelEdit();

        $this->assertFalse($component->currentValue);
    }

    public function testCancelEditDoesNotPersistUnsavedChange(): void
    {
        $entity = $this->createEntity(true);
        $id     = $entity->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'active');

        $component->currentValue = false;
        $component->cancelEdit();

        $this->em->clear();
        $this->assertTrue($this->em->find(FieldTestEntity::class, $id)?->getActive());
    }
}
