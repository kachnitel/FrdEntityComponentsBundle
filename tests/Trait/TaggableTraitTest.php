<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaggableTrait::class)]
#[Group('trait')]
class TaggableTraitTest extends TestCase
{
    private MockTaggableEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new MockTaggableEntity();
    }

    // ── initializeTags() ───────────────────────────────────────────────────────

    public function testInitializeTagsCreatesEmptyCollection(): void
    {
        $this->entity->initializeTags();
        $tags = $this->entity->getTags();

        $this->assertInstanceOf(Collection::class, $tags);
        $this->assertEmpty($tags);
    }

    // ── getTags() ──────────────────────────────────────────────────────────────

    public function testGetTagsReturnsCollection(): void
    {
        $this->entity->initializeTags();
        $tags = $this->entity->getTags();

        $this->assertInstanceOf(Collection::class, $tags);
    }

    public function testGetTagsReturnsEmptyCollectionInitially(): void
    {
        $this->entity->initializeTags();
        $tags = $this->entity->getTags();

        $this->assertCount(0, $tags);
    }

    // ── addTag() ───────────────────────────────────────────────────────────────

    public function testAddTagAddsTagToCollection(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        $this->entity->addTag($tag);

        $this->assertCount(1, $this->entity->getTags());
        $this->assertTrue($this->entity->getTags()->contains($tag));
    }

    public function testAddTagReturnsSelfForFluentInterface(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        $result = $this->entity->addTag($tag);

        $this->assertSame($this->entity, $result);
    }

    public function testAddTagPreventsNullTags(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        // Add the same tag twice
        $this->entity->addTag($tag);
        $this->entity->addTag($tag);

        // Should still be count 1 due to contains() check
        $this->assertCount(1, $this->entity->getTags());
    }

    public function testAddTagMultipleTags(): void
    {
        $this->entity->initializeTags();
        $tag1 = $this->createMock(MockTag::class);
        $tag2 = $this->createMock(MockTag::class);

        $this->entity->addTag($tag1);
        $this->entity->addTag($tag2);

        $this->assertCount(2, $this->entity->getTags());
        $this->assertTrue($this->entity->getTags()->contains($tag1));
        $this->assertTrue($this->entity->getTags()->contains($tag2));
    }

    // ── removeTag() ────────────────────────────────────────────────────────────

    public function testRemoveTagRemovesTag(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        $this->entity->addTag($tag);
        $this->entity->removeTag($tag);

        $this->assertEmpty($this->entity->getTags());
    }

    public function testRemoveTagReturnsSelfForFluentInterface(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        $this->entity->addTag($tag);
        $result = $this->entity->removeTag($tag);

        $this->assertSame($this->entity, $result);
    }

    public function testRemoveTagDoesNotErrorOnNonExistentTag(): void
    {
        $this->entity->initializeTags();
        $tag = $this->createMock(MockTag::class);

        // Should not throw
        $this->entity->removeTag($tag);

        $this->assertEmpty($this->entity->getTags());
    }

    public function testRemoveTagPartialRemoval(): void
    {
        $this->entity->initializeTags();
        $tag1 = $this->createMock(MockTag::class);
        $tag2 = $this->createMock(MockTag::class);

        $this->entity->addTag($tag1);
        $this->entity->addTag($tag2);
        $this->entity->removeTag($tag1);

        $this->assertCount(1, $this->entity->getTags());
        $this->assertFalse($this->entity->getTags()->contains($tag1));
        $this->assertTrue($this->entity->getTags()->contains($tag2));
    }

    // ── Chaining ───────────────────────────────────────────────────────────────

    public function testCanChainAddAndRemoveOperations(): void
    {
        $this->entity->initializeTags();
        $tag1 = $this->createMock(MockTag::class);
        $tag2 = $this->createMock(MockTag::class);

        $result = $this->entity
            ->addTag($tag1)
            ->addTag($tag2)
            ->removeTag($tag1);

        $this->assertSame($this->entity, $result);
        $this->assertCount(1, $this->entity->getTags());
        $this->assertTrue($this->entity->getTags()->contains($tag2));
    }
}

// Mock implementation for testing
/**
 * @implements TaggableInterface<MockTag>
 */
class MockTaggableEntity implements TaggableInterface
{
    /** @use TaggableTrait<MockTag> */
    use TaggableTrait {
        initializeTags as public;
    }

    public function __construct()
    {
        $this->initializeTags();
    }
}

class MockTag implements TagInterface
{
    public function getId(): int { return rand(1, 1000); }
    public function getValue(): ?string { return null; }
    public function getDisplayName(): ?string { return null; }
    public function getCategory(): ?string { return null; }
    public function getCategoryColor(): string { return '#000000'; }
}
