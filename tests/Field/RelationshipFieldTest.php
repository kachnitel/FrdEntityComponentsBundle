<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\RelationshipField;
use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestOwnerEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestTagEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(RelationshipField::class)]
#[UsesClass(DefaultEditabilityResolver::class)]
#[UsesClass(KachnitelEntityComponentsBundle::class)]
#[UsesClass(AttachmentManagerPass::class)]
#[Group('field')]
#[Group('field-relationship')]
class RelationshipFieldTest extends FieldTestCase
{
    /**
     * @return array{owner: FieldTestOwnerEntity, tag1: FieldTestTagEntity, tag2: FieldTestTagEntity}
     */
    private function createFixtures(): array
    {
        $tag1 = new FieldTestTagEntity('Electronics');
        $tag2 = new FieldTestTagEntity('Books');
        $this->em->persist($tag1);
        $this->em->persist($tag2);

        $owner = new FieldTestOwnerEntity('Widget');
        $owner->setPrimaryTag($tag1);
        $this->em->persist($owner);
        $this->em->flush();

        return ['owner' => $owner, 'tag1' => $tag1, 'tag2' => $tag2];
    }

    private function getComponent(): RelationshipField
    {
        return $this->getFieldComponent(RelationshipField::class);
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountInitializesSelectedIdFromAssociation(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $this->assertSame($fixtures['tag1']->getId(), $component->selectedId);
    }

    public function testMountWithNullAssociationSetsSelectedIdNull(): void
    {
        $owner = new FieldTestOwnerEntity('No tag');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->mount($owner, 'primaryTag');

        $this->assertNull($component->selectedId);
    }

    // ── canEdit() ─────────────────────────────────────────────────────────────

    public function testCanEditReturnsTrueForAssociationProperty(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $this->assertTrue($component->canEdit());
    }

    // ── getSelectedLabel() ────────────────────────────────────────────────────

    public function testGetSelectedLabelResolvesToStringMethod(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $this->assertSame('Electronics', $component->getSelectedLabel());
    }

    public function testGetSelectedLabelReturnsNullWhenNoSelection(): void
    {
        $owner = new FieldTestOwnerEntity('No tag');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->mount($owner, 'primaryTag');

        $this->assertNull($component->getSelectedLabel());
    }

    public function testGetSelectedLabelReturnsFallbackForMissingEntity(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->selectedId = 99999;
        $this->assertSame('#99999', $component->getSelectedLabel());
    }

    // ── LiveActions: select() & clear() ───────────────────────────────────────

    public function testSelectUpdatesSelectedIdAndClearsSearchQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->searchQuery = 'Books';
        $component->select($fixtures['tag2']->getId());

        $this->assertSame($fixtures['tag2']->getId(), $component->selectedId);
        $this->assertSame('', $component->searchQuery);
    }

    public function testClearNullifiesSelectionAndClearsSearchQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->searchQuery = 'something';
        $component->clear();

        $this->assertNull($component->selectedId);
        $this->assertSame('', $component->searchQuery);
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSavePersistsNewRelationship(): void
    {
        $fixtures  = $this->createFixtures();
        $ownerId   = $fixtures['owner']->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->select($fixtures['tag2']->getId());
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestOwnerEntity::class, $ownerId);
        $this->assertSame($fixtures['tag2']->getId(), $reloaded?->getPrimaryTag()?->getId());
    }

    public function testSaveClearsRelationshipWhenSelectedIdIsNull(): void
    {
        $fixtures  = $this->createFixtures();
        $ownerId   = $fixtures['owner']->getId();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->clear();
        $component->save();

        $this->em->clear();
        $reloaded = $this->em->find(FieldTestOwnerEntity::class, $ownerId);
        $this->assertNull($reloaded?->getPrimaryTag());
    }

    public function testSaveExitsEditModeAndSetsSuccessFlag(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->save();

        $this->assertFalse($component->editMode);
        $this->assertTrue($component->saveSuccess);
    }

    public function testSaveThrowsForNonAssociationProperty(): void
    {
        $owner = new FieldTestOwnerEntity('Product');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($owner, 'title');
        $component->selectedId = 999;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not a recognised Doctrine association');

        $component->save();
    }

    public function testSaveThrowsWhenSelectedEntityNotFound(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->selectedId = 99999;

        $this->expectException(\RuntimeException::class);

        $component->save();
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditRevertsToOriginalSelectedId(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->select($fixtures['tag2']->getId());
        $component->cancelEdit();

        $this->assertFalse($component->editMode);
        $this->assertSame($fixtures['tag1']->getId(), $component->selectedId);
        $this->assertSame('', $component->searchQuery);
    }

    public function testCancelEditClearsErrorMessage(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode     = true;
        $component->errorMessage = 'old error';
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->cancelEdit();

        $this->assertSame('', $component->errorMessage);
    }

    // ── getSearchResults() ────────────────────────────────────────────────────

    public function testGetSearchResultsReturnsEmptyArrayForBlankQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->searchQuery = '';
        $this->assertSame([], $component->getSearchResults());
    }

    public function testGetSearchResultsFindsMatchingEntities(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->searchQuery = 'lectron';
        $results = $component->getSearchResults();

        $this->assertCount(1, $results);
        $this->assertSame($fixtures['tag1']->getId(), $results[0]['id']);
        $this->assertSame('Electronics', $results[0]['label']);
    }

    public function testGetSearchResultsReturnsEmptyArrayForNoMatch(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        $component->searchQuery = 'xyzzy-no-match';
        $this->assertSame([], $component->getSearchResults());
    }

    // ── activateEditing() ─────────────────────────────────────────────────────

    public function testActivateEditingClearsFeedbackState(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');
        $component->errorMessage = 'Stale error';
        $component->saveSuccess  = true;
        $component->editMode     = false;

        $component->activateEditing();

        $this->assertSame('', $component->errorMessage);
        $this->assertFalse($component->saveSuccess);
        $this->assertTrue($component->editMode);
    }
}
