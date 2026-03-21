<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

/**
 * Decides whether an entity property may be edited inline.
 *
 * Called in two places by AbstractEditableField:
 *   1. canEdit() — to show/hide the ✎ trigger in display mode
 *   2. save()    — as a security guard before any value is written
 *
 * The default implementation ({@see DefaultEditabilityResolver}) allows editing
 * any writable property with no role checks. Override this binding in your app's
 * service configuration to enforce your own policy (e.g. AdminEditabilityResolver
 * in kachnitel/admin-bundle adds voter + attribute checks).
 *
 * @example Custom resolver registration in services.yaml:
 * ```yaml
 * Kachnitel\EntityComponentsBundle\Field\EditabilityResolverInterface:
 *     alias: App\Field\MyEditabilityResolver
 * ```
 */
interface EditabilityResolverInterface
{
    /**
     * Return true if the given property on the given entity may be edited inline.
     *
     * Implementations are expected to check any combination of:
     *   - Whether the property has a setter (writable)
     *   - Whether the current user has the required role/permission
     *   - Whether entity state allows editing (e.g. not archived)
     *
     * This method is called on every LiveComponent re-render, so keep it fast.
     */
    public function canEdit(object $entity, string $property): bool;
}
