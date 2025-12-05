<?php

namespace Kachnitel\EntityComponentsBundle\Interface;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for entities that support tagging
 */
interface TaggableInterface
{
    /**
     * Get all tags associated with this entity
     *
     * @return Collection<int, TagInterface>
     */
    public function getTags(): Collection;

    /**
     * Add a tag to this entity
     */
    public function addTag(TagInterface $tag): self;

    /**
     * Remove a tag from this entity
     */
    public function removeTag(TagInterface $tag): self;
}
