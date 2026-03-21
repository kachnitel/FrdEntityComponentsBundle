<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\CollectionField;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestOwnerEntity;
use Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures\FieldTestTagEntity;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\CollectionField
 * @group field
 * @group field-collection
 */
class CollectionFieldTest extends FieldTestCase
{
    /**
     * @return array{owner: FieldTestOwnerEntity, tag1: FieldTestTagEntity, tag2: FieldTestTagEntity, tag3: FieldTestTagEntity}
     */
    private function createFixtures(): array
    {
        $tag1 = new FieldTestTagEntity('Tag 1');
        $tag2 = new FieldTestTagEntity('Tag 2');
        $tag3 = new FieldTestTagEntity('Tag 3');
        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($tag3);

        $owner = new FieldTestOwnerEntity('Post');
        $owner->addTag($tag1);
        $owner->addTag($tag2);
        $this->em->persist($owner);
        $this->em->flush();

        return ['owner' => $owner, 'tag1' => $tag1, 'tag2' => $tag2, 'tag3' => $tag3];
    }

    private function getComponent(): CollectionField
    {
        return $this->getFieldComponent(CollectionField::class);
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountInitializesSelectedIdsFromCollection(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $this->assertCount(2, $component->selectedIds);
        $this->assertContains($fixtures['tag1']->getId(), $component->selectedIds);
        $this->assertContains($fixtures['tag2']->getId(), $component->selectedIds);
    }

    public function testMountWithEmptyCollectionSetsEmptyArray(): void
    {
        $owner = new FieldTestOwnerEntity('Empty');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($owner, 'tags');

        $this->assertSame([], $component->selectedIds);
    }

    // ── getSelectedItems() ────────────────────────────────────────────────────

    public function testGetSelectedItemsReturnsIdAndLabel(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $items = $component->getSelectedItems();

        $this->assertCount(2, $items);
        $this->assertArrayHasKey('id', $items[0]);
        $this->assertArrayHasKey('label', $items[0]);
    }

    public function testGetSelectedItemsResolvesLabelsViaBatchQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $items = $component->getSelectedItems();

        $labelById = array_combine(
            array_column($items, 'id'),
            array_column($items, 'label'),
        );

        $this->assertSame('Tag 1', $labelById[$fixtures['tag1']->getId()]);
        $this->assertSame('Tag 2', $labelById[$fixtures['tag2']->getId()]);
    }

    public function testGetSelectedItemsReturnsEmptyArrayWhenNoIds(): void
    {
        $owner = new FieldTestOwnerEntity('Empty');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($owner, 'tags');

        $this->assertSame([], $component->getSelectedItems());
    }

    public function testGetSelectedItemsFallsBackToIdLabelForMissingEntity(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->selectedIds[] = 99999;

        $items = $component->getSelectedItems();

        $missing = null;
        foreach ($items as $item) {
            if ($item['id'] === 99999) {
                $missing = $item;
                break;
            }
        }

        $this->assertNotNull($missing, 'Missing ID must still appear in results');
        $this->assertSame('#99999', $missing['label']);
    }

    public function testGetSelectedItemsPreservesSelectionOrder(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        // Reverse the natural order
        $component->selectedIds = array_reverse($component->selectedIds);

        $items = $component->getSelectedItems();
        $this->assertSame($component->selectedIds[0], $items[0]['id']);
        $this->assertSame($component->selectedIds[1], $items[1]['id']);
    }

    // ── addItem() & removeItem() ──────────────────────────────────────────────

    public function testAddItemAppendsIdAndClearsSearchQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->searchQuery = 'search text';
        $component->addItem($fixtures['tag3']->getId());

        $this->assertContains($fixtures['tag3']->getId(), $component->selectedIds);
        $this->assertCount(3, $component->selectedIds);
        $this->assertSame('', $component->searchQuery);
    }

    public function testAddItemIgnoresDuplicates(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->addItem($fixtures['tag1']->getId());

        $this->assertCount(2, $component->selectedIds);
    }

    public function testRemoveItemRemovesIdFromSelection(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->removeItem($fixtures['tag1']->getId());

        $this->assertCount(1, $component->selectedIds);
        $this->assertNotContains($fixtures['tag1']->getId(), $component->selectedIds);
    }

    // ── save() ────────────────────────────────────────────────────────────────

    public function testSaveAddsAndRemovesEntitiesCorrectly(): void
    {
        $fixtures = $this->createFixtures();
        $ownerId  = $fixtures['owner']->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        // Remove tag1, keep tag2, add tag3
        $component->removeItem($fixtures['tag1']->getId());
        $component->addItem($fixtures['tag3']->getId());
        $component->save();

        $this->em->clear();
        $reloaded    = $this->em->find(FieldTestOwnerEntity::class, $ownerId);
        $reloadedIds = array_map(
            fn(FieldTestTagEntity $t) => $t->getId(),
            $reloaded?->getTags()->toArray() ?? [],
        );

        $this->assertCount(2, $reloadedIds);
        $this->assertNotContains($fixtures['tag1']->getId(), $reloadedIds);
        $this->assertContains($fixtures['tag2']->getId(), $reloadedIds);
        $this->assertContains($fixtures['tag3']->getId(), $reloadedIds);
    }

    public function testSaveThrowsForNonAssociationProperty(): void
    {
        $owner = new FieldTestOwnerEntity('Post');
        $this->em->persist($owner);
        $this->em->flush();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($owner, 'title');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not a recognised Doctrine association');

        $component->save();
    }

    // ── cancelEdit() ─────────────────────────────────────────────────────────

    public function testCancelEditResetsSelectedIdsToPersistedState(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->removeItem($fixtures['tag1']->getId());
        $component->addItem($fixtures['tag3']->getId());
        $component->searchQuery = 'some query';

        $component->cancelEdit();

        $this->assertFalse($component->editMode);
        $this->assertSame('', $component->searchQuery);
        $this->assertCount(2, $component->selectedIds);
        $this->assertContains($fixtures['tag1']->getId(), $component->selectedIds);
        $this->assertNotContains($fixtures['tag3']->getId(), $component->selectedIds);
    }

    public function testCancelEditDoesNotPersistChanges(): void
    {
        $fixtures = $this->createFixtures();
        $ownerId  = $fixtures['owner']->getId();

        $component = $this->getComponent();
        $component->editMode = true;
        $component->mount($fixtures['owner'], 'tags');

        $component->removeItem($fixtures['tag1']->getId());
        $component->cancelEdit();

        $this->em->clear();
        $reloaded    = $this->em->find(FieldTestOwnerEntity::class, $ownerId);
        $reloadedIds = array_map(
            fn(FieldTestTagEntity $t) => $t->getId(),
            $reloaded?->getTags()->toArray() ?? [],
        );

        $this->assertContains($fixtures['tag1']->getId(), $reloadedIds);
    }

    // ── getSearchResults() ────────────────────────────────────────────────────

    public function testGetSearchResultsReturnsEmptyForBlankQuery(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'tags');

        $component->searchQuery = '';
        $this->assertSame([], $component->getSearchResults());
    }

    public function testGetSearchResultsFindsMatchingEntities(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['owner'], 'tags');

        $component->searchQuery = 'Tag 3';
        $results = $component->getSearchResults();

        $this->assertCount(1, $results);
        $this->assertSame($fixtures['tag3']->getId(), $results[0]['id']);
        $this->assertSame('Tag 3', $results[0]['label']);
    }
}
