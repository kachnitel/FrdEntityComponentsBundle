<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Permissive default implementation of EditabilityResolverInterface.
 *
 * Allows inline editing of any property that has a setter. No role checks,
 * no attribute checks. Suitable for internal/admin-only apps where access
 * control is handled at the controller/route level.
 *
 * Replace this binding in services.yaml to enforce stricter policy:
 *
 * ```yaml
 * Kachnitel\EntityComponentsBundle\Field\EditabilityResolverInterface:
 *     alias: App\Field\MyEditabilityResolver
 * ```
 *
 * kachnitel/admin-bundle registers AdminEditabilityResolver which adds
 * Symfony voter checks and #[AdminColumn] attribute support.
 */
final class DefaultEditabilityResolver implements EditabilityResolverInterface
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {}

    public function canEdit(object $entity, string $property): bool
    {
        return $this->propertyAccessor->isWritable($entity, $property);
    }
}
