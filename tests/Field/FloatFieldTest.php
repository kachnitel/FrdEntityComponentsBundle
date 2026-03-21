<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\FloatField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\FloatField
 * @group field
 * @group field-float
 */
class FloatFieldTest extends FieldTestCase
{
    private function createEntity(float $score = 3.14): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setScore($score);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): FloatField
    {
        return $this->getFieldComponent(FloatField::class);
    }

    public function testMountPopulatesCurrentValue(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(9.99), 'score');

        $this->assertEqualsWithDelta(9.99, $component->currentValue, 0.0001);
    }

    public function testMountWithZero(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(0.0), 'score');

        $this->assertSame(0.0, $component->currentValue);
    }

    public function testSavePersistsFloat(): void
    {
        $entity    = $this->createEntity(1.0);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'score');

        $component->currentValue = 7.5;
        $component->save();

        $this->em->clear();
        $this->assertEqualsWithDelta(7.5, $this->em->find(FieldTestEntity::class, $id)?->getScore(), 0.0001);
    }

    public function testCancelEditRevertsToPersistedFloat(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(3.14), 'score');

        $component->currentValue = 999.99;
        $component->cancelEdit();

        $this->assertEqualsWithDelta(3.14, $component->currentValue, 0.0001);
    }

    /** Guards against (float) null = 0.0 false positive after cancelEdit. */
    public function testCancelEditDoesNotCoerceNullToZero(): void
    {
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($this->createEntity(5.0), 'score');

        $component->currentValue = 88.8;
        $component->cancelEdit();

        $this->assertEqualsWithDelta(5.0, $component->currentValue, 0.0001);
    }
}
