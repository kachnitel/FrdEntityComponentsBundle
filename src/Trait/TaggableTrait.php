<?php

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;

/**
 * Trait for entities that support tagging
 *
 * Note: You must add the ORM\ManyToMany mapping in your entity class:
 * #[ORM\ManyToMany(targetEntity: YourTagClass::class)]
 * private Collection $tags;
 *
 *
 * @template T of TagInterface
 */
trait TaggableTrait
{
    /** @var Collection<int, T> $tags */
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
