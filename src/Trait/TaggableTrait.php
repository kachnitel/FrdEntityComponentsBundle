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
 */
trait TaggableTrait
{
    private Collection $tags;

    /**
     * Initialize the tags collection (call this from your entity's __construct)
     */
    private function initializeTags(): void
    {
        $this->tags = new ArrayCollection();
    }

    /**
     * @return Collection<int, TagInterface>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(TagInterface $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(TagInterface $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
