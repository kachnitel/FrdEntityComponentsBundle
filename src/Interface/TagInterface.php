<?php

namespace Frd\EntityComponentsBundle\Interface;

/**
 * Interface for Tag entities
 */
interface TagInterface
{
    /**
     * Get the unique identifier for this tag
     */
    public function getId(): ?int;

    /**
     * Get the tag value/name
     */
    public function getValue(): ?string;

    /**
     * Get the display name (falls back to value if not set)
     */
    public function getDisplayName(): ?string;

    /**
     * Get the tag category (optional grouping)
     */
    public function getCategory(): ?string;
}
