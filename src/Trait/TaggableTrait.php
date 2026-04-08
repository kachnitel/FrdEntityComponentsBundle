<?php

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;

/**
 * Trait for entities that support tagging
 *
 * Note: You must add the following to your Doctrine configuration
 * to resolve the TagInterface to your actual Tag entity:
 *
 * ```
 * doctrine:
 *   orm:
 *       resolve_target_entities:
 *           Kachnitel\EntityComponentsBundle\Interface\TagInterface: App\Entity\Tag
 *           Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface: App\Entity\Attachment
 * ```
 *
 * @template T of TagInterface
 */
trait TaggableTrait
{
    /** @var Collection<int, T> $tags */
    #[ORM\ManyToMany(targetEntity: TagInterface::class)]
    private Collection $tags;

    /**
     * Initialize the tags collection (call this from your entity's __construct)
     */
    private function initializeTags(): void
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @return Collection<int, T>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param T $tag
     */
    public function addTag(TagInterface $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * @param T $tag
     */
    public function removeTag(TagInterface $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
