<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components;

/**
 * Configuration DTO for the SelectRelationship component.
 *
 * All properties are optional — instantiate with only what you need:
 *
 * ```twig
 * <twig:K:Entity:SelectRelationship
 *     :entity="order"
 *     property="region"
 *     :options="new SelectRelationshipOptions(
 *         placeholder: '— Region —',
 *         role: 'ROLE_ORDER_REGION_EDIT',
 *     )"
 * />
 * ```
 */
final class SelectRelationshipOptions
{
    /**
     * @param string               $placeholder     Empty option text
     * @param string               $valueProperty   Entity property used as option value
     * @param string               $displayProperty Entity property used as option label
     * @param bool                 $disableEmpty    Disable the empty/placeholder option
     * @param array<string, mixed> $filter          Simple criteria passed to findBy()
     * @param string|null          $repositoryMethod Custom repository method name
     * @param array<int, mixed>    $repositoryArgs  Arguments for the custom repository method
     * @param string|null          $role            Role required to show the editable select
     * @param string|null          $viewRole        Role required to show the read-only display
     * @param bool                 $disabled        Force read-only regardless of role
     * @param string|null          $label           Optional label shown above/beside the input
     *
     * @SuppressWarnings(ExcessiveParameterList) — DTO constructor; every parameter is a
     * documented, named config option. Splitting into sub-objects would add indirection
     * without reducing actual complexity for callers.
     */
    public function __construct(
        public readonly string $placeholder = '-',
        public readonly string $valueProperty = 'id',
        public readonly string $displayProperty = 'name',
        public readonly bool $disableEmpty = false,
        public readonly array $filter = [],
        public readonly ?string $repositoryMethod = null,
        public readonly array $repositoryArgs = [],
        public readonly ?string $role = null,
        public readonly ?string $viewRole = null,
        public readonly bool $disabled = false,
        public readonly ?string $label = null,
    ) {}
}