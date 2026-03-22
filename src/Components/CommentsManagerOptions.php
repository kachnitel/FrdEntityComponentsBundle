<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components;

/**
 * Configuration DTO for the CommentsManager component.
 *
 * ```twig
 * <twig:K:Entity:CommentsManager
 *     :entity="article"
 *     commentClass="App\\Entity\\Comment"
 *     :options="new CommentsManagerOptions(readOnly: true)"
 * />
 * ```
 */
final class CommentsManagerOptions
{
    /**
     * @param bool   $readOnly Disable new comments and deletion
     * @param string $property Collection property name on the entity
     */
    public function __construct(
        public readonly bool $readOnly = false,
        public readonly string $property = 'comments',
    ) {}
}
