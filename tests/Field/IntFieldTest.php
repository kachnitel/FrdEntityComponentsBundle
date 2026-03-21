<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\IntField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(IntField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-int')]
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

    // ── mount() ───────────────────────────────────────────────────────────────

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

    // ── canEdit() ─────────────────────────────────────────────────────────────

    public function testCanEditReturnsTrueForWritableProperty(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'count');

        $this->assertTrue($component->canEdit());
    }

    // ── save() ────────────────────────────────────────────────────────────────

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

    public function testSaveExitsEditModeAndSetsSuccessFlag(): void
    {
        $entity    = $this->createEntity(10);
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'count');

        $component->currentValue = 20;
        $component->save();

        $this->assertFalse($component->editMode);
        $this->assertTrue($component->saveSuccess);
        $this->assertSame('', $component->errorMessage);
    }

    public function testSavePersistsZero(): void
    {
        $entity    = $this->createEntity(5);
        $id        = $entity->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'count');

        $component->currentValue = 0;
        $component->save();

        $this->em->clear();
        $this->assertSame(0, $this->em->find(FieldTestEntity::class, $id)?->getCount());
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

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

    public function testCancelEditDoesNotPersistUnsavedInput(): void
    {
        $entity = $this->createEntity(42);
        $id     = $entity->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'count');

        $component->currentValue = 9999;
        $component->cancelEdit();

        $this->em->clear();
        $this->assertSame(42, $this->em->find(FieldTestEntity::class, $id)?->getCount());
    }

    public function testCancelEditExitsEditModeAndClearsError(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'old error';
        $component->mount($entity, 'count');

        $component->cancelEdit();

        $this->assertFalse($component->editMode);
        $this->assertSame('', $component->errorMessage);
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'count');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
