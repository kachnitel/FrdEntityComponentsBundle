<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Trait;

use Doctrine\Common\Collections\Collection;
use Kachnitel\EntityComponentsBundle\Interface\AttachableInterface;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;
use Kachnitel\EntityComponentsBundle\Trait\AttachableTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttachableTrait::class)]
#[Group('trait')]
class AttachableTraitTest extends TestCase
{
    private MockAttachableEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new MockAttachableEntity();
    }

    // ── initializeAttachments() ────────────────────────────────────────────────

    public function testInitializeAttachmentsCreatesEmptyCollection(): void
    {
        $this->entity->initializeAttachments();
        $attachments = $this->entity->getAttachments();

        $this->assertInstanceOf(Collection::class, $attachments);
        $this->assertEmpty($attachments);
    }

    // ── getAttachments() ───────────────────────────────────────────────────────

    public function testGetAttachmentsReturnsCollection(): void
    {
        $this->entity->initializeAttachments();
        $attachments = $this->entity->getAttachments();

        $this->assertInstanceOf(Collection::class, $attachments);
    }

    public function testGetAttachmentsReturnsEmptyCollectionInitially(): void
    {
        $this->entity->initializeAttachments();
        $attachments = $this->entity->getAttachments();

        $this->assertCount(0, $attachments);
    }

    // ── addAttachment() ────────────────────────────────────────────────────────

    public function testAddAttachmentAddsAttachmentToCollection(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        $this->entity->addAttachment($attachment);

        $this->assertCount(1, $this->entity->getAttachments());
        $this->assertTrue($this->entity->getAttachments()->contains($attachment));
    }

    public function testAddAttachmentReturnsSelfForFluentInterface(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        $result = $this->entity->addAttachment($attachment);

        $this->assertSame($this->entity, $result);
    }

    public function testAddAttachmentPreventsNullAttachments(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        // Add the same attachment twice
        $this->entity->addAttachment($attachment);
        $this->entity->addAttachment($attachment);

        // Should still be count 1 due to contains() check
        $this->assertCount(1, $this->entity->getAttachments());
    }

    public function testAddAttachmentMultipleAttachments(): void
    {
        $this->entity->initializeAttachments();
        $attachment1 = $this->createMock(MockAttachment::class);
        $attachment2 = $this->createMock(MockAttachment::class);

        $this->entity->addAttachment($attachment1);
        $this->entity->addAttachment($attachment2);

        $this->assertCount(2, $this->entity->getAttachments());
        $this->assertTrue($this->entity->getAttachments()->contains($attachment1));
        $this->assertTrue($this->entity->getAttachments()->contains($attachment2));
    }

    // ── removeAttachment() ─────────────────────────────────────────────────────

    public function testRemoveAttachmentRemovesAttachment(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        $this->entity->addAttachment($attachment);
        $this->entity->removeAttachment($attachment);

        $this->assertEmpty($this->entity->getAttachments());
    }

    public function testRemoveAttachmentReturnsSelfForFluentInterface(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        $this->entity->addAttachment($attachment);
        $result = $this->entity->removeAttachment($attachment);

        $this->assertSame($this->entity, $result);
    }

    public function testRemoveAttachmentDoesNotErrorOnNonExistentAttachment(): void
    {
        $this->entity->initializeAttachments();
        $attachment = $this->createMock(MockAttachment::class);

        // Should not throw
        $this->entity->removeAttachment($attachment);

        $this->assertEmpty($this->entity->getAttachments());
    }

    public function testRemoveAttachmentPartialRemoval(): void
    {
        $this->entity->initializeAttachments();
        $attachment1 = $this->createMock(MockAttachment::class);
        $attachment2 = $this->createMock(MockAttachment::class);

        $this->entity->addAttachment($attachment1);
        $this->entity->addAttachment($attachment2);
        $this->entity->removeAttachment($attachment1);

        $this->assertCount(1, $this->entity->getAttachments());
        $this->assertFalse($this->entity->getAttachments()->contains($attachment1));
        $this->assertTrue($this->entity->getAttachments()->contains($attachment2));
    }

    // ── Chaining ───────────────────────────────────────────────────────────────

    public function testCanChainAddAndRemoveOperations(): void
    {
        $this->entity->initializeAttachments();
        $attachment1 = $this->createMock(MockAttachment::class);
        $attachment2 = $this->createMock(MockAttachment::class);

        $result = $this->entity
            ->addAttachment($attachment1)
            ->addAttachment($attachment2)
            ->removeAttachment($attachment1);

        $this->assertSame($this->entity, $result);
        $this->assertCount(1, $this->entity->getAttachments());
        $this->assertTrue($this->entity->getAttachments()->contains($attachment2));
    }
}

// Mock implementation for testing
/**
 * @implements AttachableInterface<MockAttachment>
 */
class MockAttachableEntity implements AttachableInterface
{
    /** @use AttachableTrait<MockAttachment> */
    use AttachableTrait {
        initializeAttachments as public;
    }

    public function __construct()
    {
        $this->initializeAttachments();
    }
}

class MockAttachment implements AttachmentInterface
{
    public function getId(): int { return rand(1, 1000); }
    public function getUrl(): ?string { return null; }
    public function getMimeType(): ?string { return null; }
    public function getPath(): ?string { return null; }
}
