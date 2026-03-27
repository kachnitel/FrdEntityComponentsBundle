<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Kachnitel\EntityComponentsBundle\Components\TagManager;
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures\ComponentTestTag;
use Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures\ComponentTestTaggableEntity;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional tests for TagManager using real SQLite ORM.
 *
 * Covers mount(), getEntity(), addTag(), removeTag(), toggleTagList(),
 * getAllTags(), and getTags(). saveChanges() is omitted because it calls
 * dispatchBrowserEvent() which requires a full LiveComponent HTTP context.
 */
#[CoversClass(TagManager::class)]
#[Group('component')]
#[Group('component-tag-manager')]
class TagManagerFunctionalTest extends ComponentFunctionalTestCase
{
    /**
     * @return array{entity: ComponentTestTaggableEntity, tag1: ComponentTestTag, tag2: ComponentTestTag, tag3: ComponentTestTag}
     */
    private function createFixtures(): array
    {
        $tag1   = new ComponentTestTag('php');
        $tag2   = new ComponentTestTag('symfony');
        $tag3   = new ComponentTestTag('doctrine');
        $entity = new ComponentTestTaggableEntity('My Article');

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($tag3);
        $entity->addTag($tag1);
        $entity->addTag($tag2);
        $this->em->persist($entity);
        $this->em->flush();

        return ['entity' => $entity, 'tag1' => $tag1, 'tag2' => $tag2, 'tag3' => $tag3];
    }

    /**
     * @return TagManager<ComponentTestTag>
     */
    private function getComponent(): TagManager
    {
        return $this->factory->get('K:Entity:TagManager');
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountSetsEntityClassAndId(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $this->assertSame(ComponentTestTaggableEntity::class, $component->entityClass);
        $this->assertSame($fixtures['entity']->getId(), $component->entityId);
    }

    public function testMountInitializesTagIdsFromEntity(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $this->assertCount(2, $component->tagIds);
        $this->assertContains($fixtures['tag1']->getId(), $component->tagIds);
        $this->assertContains($fixtures['tag2']->getId(), $component->tagIds);
    }

    public function testMountWithNoTagsInitializesEmptyArray(): void
    {
        $entity = new ComponentTestTaggableEntity('Empty');
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->mount($entity, ComponentTestTag::class);

        $this->assertSame([], $component->tagIds);
    }

    public function testMountThrowsForEntityWithoutGetId(): void
    {
        /** @implements TaggableInterface<ComponentTestTag> */
        $badEntity = new BadTaggableEntity();

        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('getId()');

        $component->mount($badEntity, ComponentTestTag::class);
    }

    public function testMountThrowsForClassNotImplementingTagInterface(): void
    {
        $entity = new ComponentTestTaggableEntity('test');
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TagInterface');

        $component->mount($entity, \stdClass::class); // @phpstan-ignore argument.type
    }

    // ── getEntity() ───────────────────────────────────────────────────────────

    public function testGetEntityReturnsCorrectEntity(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $this->assertSame($fixtures['entity'], $component->getEntity());
    }

    public function testGetEntityFetchesFromDatabaseWhenNotCached(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        // Simulate a re-render: clear the cached entity reference
        $ref = new \ReflectionProperty($component, 'entity');
        $ref->setAccessible(true);
        $ref->setValue($component, null);

        /** @var ComponentTestTaggableEntity $fetched */
        $fetched = $component->getEntity();
        $this->assertSame($fixtures['entity']->getId(), $fetched->getId());
    }

    public function testGetEntityThrowsWhenEntityNotFound(): void
    {
        $component              = $this->getComponent();
        $component->entityClass = ComponentTestTaggableEntity::class;
        $component->entityId    = 99999;

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $component->getEntity();
    }

    // ── addTag() ──────────────────────────────────────────────────────────────

    public function testAddTagAppendsNewId(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->addTag($fixtures['tag3']->getId());

        $this->assertContains($fixtures['tag3']->getId(), $component->tagIds);
        $this->assertCount(3, $component->tagIds);
    }

    public function testAddTagIgnoresDuplicates(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->addTag($fixtures['tag1']->getId());
        $component->addTag($fixtures['tag1']->getId());

        $this->assertCount(2, $component->tagIds);
    }

    // ── removeTag() ───────────────────────────────────────────────────────────

    public function testRemoveTagRemovesExistingId(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->removeTag($fixtures['tag1']->getId());

        $this->assertNotContains($fixtures['tag1']->getId(), $component->tagIds);
        $this->assertCount(1, $component->tagIds);
    }

    public function testRemoveTagReindexesArray(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->removeTag($fixtures['tag1']->getId());

        $this->assertSame(array_values($component->tagIds), $component->tagIds);
        $this->assertArrayHasKey(0, $component->tagIds);
    }

    public function testRemoveTagIsNoopForNonExistingId(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->removeTag(99999);

        $this->assertCount(2, $component->tagIds);
    }

    // ── toggleTagList() ───────────────────────────────────────────────────────

    public function testToggleTagListFlipsFromFalseToTrue(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $this->assertFalse($component->showingTags);
        $component->toggleTagList();
        $this->assertTrue($component->showingTags);
    }

    public function testToggleTagListFlipsFromTrueToFalse(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->showingTags = true;
        $component->toggleTagList();
        $this->assertFalse($component->showingTags);
    }

    // ── getAllTags() ───────────────────────────────────────────────────────────

    public function testGetAllTagsReturnsAllFromRepository(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $all = $component->getAllTags();

        $this->assertCount(3, $all);
    }

    public function testGetAllTagsReturnsEmptyWhenNoTagsExist(): void
    {
        $entity = new ComponentTestTaggableEntity('Empty');
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->mount($entity, ComponentTestTag::class);

        $this->assertSame([], $component->getAllTags());
    }

    // ── getTags() ─────────────────────────────────────────────────────────────

    public function testGetTagsFiltersToSelectedIds(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $selected    = $component->getTags();
        $selectedIds = array_map(fn($t) => $t->getId(), $selected);

        $this->assertContains($fixtures['tag1']->getId(), $selectedIds);
        $this->assertContains($fixtures['tag2']->getId(), $selectedIds);
        $this->assertNotContains($fixtures['tag3']->getId(), $selectedIds);
    }

    public function testGetTagsReturnsEmptyWhenNoTagIdsSelected(): void
    {
        $entity = new ComponentTestTaggableEntity('Empty');
        $this->em->persist($entity);
        $this->em->flush();

        $component = $this->getComponent();
        $component->mount($entity, ComponentTestTag::class);

        $this->assertEmpty($component->getTags());
    }

    public function testGetTagsReflectsAddTagAction(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->addTag($fixtures['tag3']->getId());
        $selected = $component->getTags();

        $selectedIds = array_map(fn($t) => $t->getId(), $selected);
        $this->assertContains($fixtures['tag3']->getId(), $selectedIds);
        $this->assertCount(3, $selected);
    }

    public function testGetTagsReflectsRemoveTagAction(): void
    {
        $fixtures  = $this->createFixtures();
        $component = $this->getComponent();
        $component->mount($fixtures['entity'], ComponentTestTag::class);

        $component->removeTag($fixtures['tag1']->getId());
        $selected = $component->getTags();

        $this->assertCount(1, $selected);
    }

    // ── Default properties ────────────────────────────────────────────────────

    public function testDefaultPropertiesAreCorrect(): void
    {
        $component = $this->getComponent();

        $this->assertFalse($component->readOnly);
        $this->assertFalse($component->showingTags);
        $this->assertIsArray($component->tagIds);
        $this->assertEmpty($component->tagIds);
    }
}

/**
 * Mock entity without a getId() method to test validation.
 * @implements TaggableInterface<ComponentTestTag>
 */
class BadTaggableEntity implements TaggableInterface
{
    /** @use TaggableTrait<ComponentTestTag> */
    use TaggableTrait;

    public function __construct()
    {
        $this->initializeTags();
    }
}
