<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\RelationshipField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestOwnerEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestTagEntity;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\RelationshipField
 * @group field
 * @group field-relationship
 */
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

    // ── getSelectedLabel() ────────────────────────────────────────────────────

    public function testGetSelectedLabelResolvesToStringMethod(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'primaryTag');

        // FieldTestTagEntity::__toString() returns the name
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
}
