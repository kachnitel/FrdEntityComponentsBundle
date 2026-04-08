<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;

/**
 * Provides a ManyToMany attachments collection for any entity.
 *
 * The Doctrine mapping targets {@see AttachmentInterface}. Doctrine resolves
 * the concrete class at runtime via `resolve_target_entities` in your app's
 * `doctrine.yaml`. The bundle's {@see JoinTableNormalizerSubscriber} rewrites
 * the auto-generated join table name to use your concrete class name.
 *
 * ## Uniqueness
 *
 * The inverse join column carries `unique: true`, meaning each attachment
 * belongs to exactly one parent entity. This is intentional: an uploaded file
 * is created for a specific owner (a photo for *this* product, a receipt for
 * *this* order). Shared attachments create ambiguity around ownership, access
 * control, and deletion. Override the mapping in your entity if you genuinely
 * need a file to appear on multiple entities:
 *
 * ```php
 * #[ORM\ManyToMany(targetEntity: AttachmentInterface::class, cascade: ['persist', 'remove'])]
 * #[ORM\InverseJoinColumn(unique: false)]
 * private Collection $attachments;
 * ```
 *
 * ## Setup
 *
 * 1. Configure `resolve_target_entities` in `config/packages/doctrine.yaml`:
 *
 * ```yaml
 * doctrine:
 *     orm:
 *         resolve_target_entities:
 *             Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface: App\Entity\Attachment
 * ```
 *
 * 2. Use the trait and call `initializeAttachments()` in your constructor:
 *
 * ```php
 * class Product implements AttachableInterface
 * {
 *     use AttachableTrait;
 *
 *     public function __construct() { $this->initializeAttachments(); }
 * }
 * ```
 *
 * @see \Kachnitel\EntityComponentsBundle\Interface\AttachableInterface
 *
 * @template T of AttachmentInterface
 */
trait AttachableTrait
{
    /** @var Collection<int, T> $attachments */
    #[ORM\ManyToMany(targetEntity: AttachmentInterface::class, cascade: ['persist', 'remove'])]
    #[ORM\InverseJoinColumn(unique: true)]
    private Collection $attachments;

    private function initializeAttachments(): void
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * @return Collection<int, T>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * @param T $attachment
     */
    public function addAttachment(AttachmentInterface $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * @param T $attachment
     */
    public function removeAttachment(AttachmentInterface $attachment): static
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }
}
