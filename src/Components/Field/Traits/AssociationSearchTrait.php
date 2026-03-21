<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field\Traits;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Auto-detects which entity fields to search against for live association search.
 *
 * Used by AssociationFieldTrait to power the search input in RelationshipField
 * and CollectionField components.
 *
 * ## Field priority
 *
 * Search fields are chosen in this order from the target entity's mapped fields:
 *   name → label → title → id
 *
 * The first fields that exist in the entity's metadata are used. If none of
 * name/label/title exist, the search falls back to id-only (numeric queries only).
 *
 * ## Display field priority
 *
 * DISPLAY_FIELD_PRIORITY is used by AssociationFieldTrait::resolveLabel() to pick
 * a human-readable property when __toString() is not available.
 */
trait AssociationSearchTrait
{
    /**
     * Property names tried in order when resolving a human-readable label for a
     * related entity that has no __toString() method.
     *
     * @var list<string>
     */
    protected const DISPLAY_FIELD_PRIORITY = ['name', 'label', 'title'];

    /**
     * Property names tried in order when auto-detecting search fields.
     *
     * @var list<string>
     */
    private const SEARCH_FIELD_PRIORITY = ['name', 'label', 'title', 'id'];

    /**
     * Resolve which fields to search against for the given target entity metadata.
     *
     * @param ClassMetadata<object> $metadata    Target entity Doctrine metadata
     * @param string                $entityName  Short class name (available for overrides in subclasses)
     * @param list<string>|null     $override    Explicit field list; null = auto-detect from metadata
     *
     * @return list<string> Non-empty list of field names to include in search queries
     */
    protected function resolveSearchFields(ClassMetadata $metadata, string $entityName, ?array $override): array
    {
        if ($override !== null && $override !== []) {
            return $override;
        }

        return $this->getAutoDetectedSearchFields($metadata);
    }

    /**
     * Detect searchable fields by checking SEARCH_FIELD_PRIORITY against mapped fields.
     *
     * @param ClassMetadata<object> $metadata
     * @return list<string>
     */
    private function getAutoDetectedSearchFields(ClassMetadata $metadata): array
    {
        $detected = [];

        foreach (self::SEARCH_FIELD_PRIORITY as $field) {
            if ($metadata->hasField($field)) {
                $detected[] = $field;
            }
        }

        // Always fall back to at least id so an all-numeric query still works
        return $detected !== [] ? $detected : ['id'];
    }
}
