<?php

namespace Kachnitel\EntityComponentsBundle\Interface;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for entities that support tagging
 * @see Kachnitel\EntityComponentsBundle\Trait\TaggableTrait
 *
 * @template T of TagInterface
 */
interface TaggableInterface
{
    /**
     * Get all tags associated with this entity
     *
     * @return Collection<int, T>
     */
    public function getTags(): Collection;

    /**
     * @param T $tag
     */
    public function addTag(TagInterface $tag): static;

    /**
     * @param T $tag
     */
    public function removeTag(TagInterface $tag): static;
}
