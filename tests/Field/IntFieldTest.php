<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\IntField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\IntField
 * @group field
 * @group field-int
 */
class IntFieldTest extends FieldTestCase
{
    private function createEntity(int $count = 42): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setCount($count);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): IntField
    {
        return $this->getFieldComponent(IntField::class);
    }

    public function testMountPopulatesCurrentValue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(99), 'count');

        $this->assertSame(99, $component->currentValue);
    }

    public function testMountWithZero(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(0), 'count');

        $this->assertSame(0, $component->currentValue);
    }

    public function testMountWithNegative(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(-5), 'count');

        $this->assertSame(-5, $component->currentValue);
    }

    public function testSavePersistsInt(): void
    {
        $entity    = $this->createEntity(10);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'count');

        $component->currentValue = 777;
        $component->save();

        $this->em->clear();
        $this->assertSame(777, $this->em->find(FieldTestEntity::class, $id)?->getCount());
    }

    public function testCancelEditRevertsToPersistedValue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(42), 'count');

        $component->currentValue = 9999;
        $component->cancelEdit();

        $this->assertSame(42, $component->currentValue);
    }

    /** Guards against (int) null = 0 false positive after cancelEdit. */
    public function testCancelEditDoesNotCoerceNullToZero(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(5), 'count');

        $component->currentValue = 88;
        $component->cancelEdit();

        $this->assertSame(5, $component->currentValue);
    }
}
