<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components;

/**
 * Configuration DTO for the AttachmentManager component.
 *
 * ```twig
 * <twig:K:Entity:AttachmentManager
 *     :entity="product"
 *     attachmentClass="App\\Entity\\UploadedFile"
 *     :options="new AttachmentManagerOptions(readOnly: true)"
 * />
 * ```
 */
final class AttachmentManagerOptions
{
    /**
     * @param bool        $readOnly Disable file uploads and deletion
     * @param string      $property Collection property name on the entity
     * @param string|null $tagClass FQCN of a tag entity to show TagManager per attachment
     */
    public function __construct(
        public readonly bool $readOnly = false,
        public readonly string $property = 'attachments',
        public readonly ?string $tagClass = null,
    ) {}
}
