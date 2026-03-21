<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\BoolField;
use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(BoolField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-bool')]
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

    // ── canEdit() ─────────────────────────────────────────────────────────────

    public function testCanEditReturnsTrueForWritableProperty(): void
    {
        $component = $this->getComponent();
        $component->mount($this->createEntity(), 'active');

        $this->assertTrue($component->canEdit());
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

    public function testSaveExitsEditModeAndSetsSuccessFlag(): void
    {
        $entity    = $this->createEntity(false);
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($entity, 'active');

        $component->currentValue = true;
        $component->save();

        $this->assertFalse($component->editMode);
        $this->assertTrue($component->saveSuccess);
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

    public function testCancelEditExitsEditModeAndClearsError(): void
    {
        $entity    = $this->createEntity(true);
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'old error';
        $component->mount($entity, 'active');

        $component->cancelEdit();

        $this->assertFalse($component->editMode);
        $this->assertSame('', $component->errorMessage);
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, 'active');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
