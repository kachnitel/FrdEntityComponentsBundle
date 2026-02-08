<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components;

use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionNamedType;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A reusable select component for entity relationships and enums.
 *
 * Automatically detects whether the property is an Entity or Enum and
 * loads appropriate options.
 */
#[AsLiveComponent('K:Entity:SelectRelationship', template: '@KachnitelEntityComponents/components/SelectRelationship.html.twig')]
final class SelectRelationship
{
    use DefaultActionTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private PropertyInfoExtractorInterface $propertyInfo,
        private PropertyAccessorInterface $propertyAccessor,
    ) {}

    /** Stored as string to avoid LiveProp union type limitation */
    #[LiveProp(writable: true, onUpdated: 'onValueChanged')]
    public ?string $value = null;

    #[LiveProp]
    public string $entityClass = '';

    #[LiveProp]
    public ?string $entityId = null;

    #[LiveProp]
    public string $property = '';

    #[LiveProp]
    public ?string $label = null;

    #[LiveProp]
    public string $placeholder = '-';

    #[LiveProp]
    public string $valueProperty = 'id';

    #[LiveProp]
    public string $displayProperty = 'name';

    #[LiveProp]
    public bool $disableEmpty = false;

    /** @var array<string, mixed> Simple criteria for findBy() */
    #[LiveProp]
    public array $filter = [];

    /** Custom repository method name (e.g., 'findByRoles') */
    #[LiveProp]
    public ?string $repositoryMethod = null;

    /** @var array<int, mixed> Arguments for custom repository method */
    #[LiveProp]
    public array $repositoryArgs = [];

    /** Role required to edit (select is shown) */
    #[LiveProp]
    public ?string $role = null;

    /** Role required to view (static display shown if edit not granted but view is) */
    #[LiveProp]
    public ?string $viewRole = null;

    #[LiveProp]
    public bool $disabled = false;

    private ?string $targetClass = null;
    private bool $isEnum = false;
    private ?object $entity = null;

    public function mount(object $entity, string $property): void
    {
        $this->entityClass = get_class($entity);
        $this->entityId = (string) $this->propertyAccessor->getValue($entity, 'id');
        $this->property = $property;
        $this->entity = $entity;

        $this->resolveTargetType();
        $this->initializeValue();
    }

    public function onValueChanged(): void
    {
        $entity = $this->getEntity();
        $setter = 'set' . ucfirst($this->property);
        if (!method_exists($entity, $setter)) {
            return;
        }

        $this->resolveTargetType();

        if ($this->value === null || $this->value === '') {
            $entity->{$setter}(null);
        } elseif ($this->isEnum && $this->targetClass !== null) {
            $entity->{$setter}($this->targetClass::from($this->value));
        } elseif ($this->targetClass !== null) {
            /** @phpstan-ignore argument.templateType */
            $targetEntity = $this->em->find($this->targetClass, $this->value);
            $entity->{$setter}($targetEntity);
        }

        $this->em->persist($entity);
        $this->em->flush();
    }

    /**
     * @return array<int, object|BackedEnum>
     */
    public function getOptions(): array
    {
        $this->resolveTargetType();

        if ($this->targetClass === null) {
            return [];
        }

        if ($this->isEnum) {
            return $this->targetClass::cases();
        }

        /** @phpstan-ignore argument.templateType */
        $repository = $this->em->getRepository($this->targetClass);

        if ($this->repositoryMethod !== null) {
            return $repository->{$this->repositoryMethod}(...$this->repositoryArgs);
        }

        if (!empty($this->filter)) {
            return $repository->findBy($this->filter);
        }

        return $repository->findAll();
    }

    public function getOptionValue(object $option): string
    {
        if ($option instanceof BackedEnum) {
            return (string) $option->value;
        }

        return (string) $this->propertyAccessor->getValue($option, $this->valueProperty);
    }

    public function getOptionLabel(object $option): string
    {
        if ($option instanceof BackedEnum) {
            if (method_exists($option, 'displayValue')) {
                return $option->displayValue();
            }
            return $option->name;
        }

        return (string) $this->propertyAccessor->getValue($option, $this->displayProperty);
    }

    public function isSelected(object $option): bool
    {
        return $this->getOptionValue($option) == $this->value;
    }

    public function getCurrentDisplayValue(): string
    {
        $currentValue = $this->propertyAccessor->getValue($this->getEntity(), $this->property);

        if ($currentValue === null) {
            return $this->placeholder;
        }

        return $this->getOptionLabel($currentValue);
    }

    public function isEnumType(): bool
    {
        $this->resolveTargetType();
        return $this->isEnum;
    }

    public function getEntity(): object
    {
        if ($this->entity === null) {
            /** @phpstan-ignore argument.templateType */
            $this->entity = $this->em->find($this->entityClass, $this->entityId);
            if ($this->entity === null) {
                throw new \RuntimeException(sprintf(
                    'Entity %s with ID %s not found',
                    $this->entityClass,
                    (string) $this->entityId
                ));
            }
        }

        return $this->entity;
    }

    /**
     * Expose entity for template (checks like entity.isDeleted)
     */
    public function getEntityObject(): object
    {
        return $this->getEntity();
    }

    private function resolveTargetType(): void
    {
        if ($this->targetClass !== null) {
            return;
        }

        $className = $this->resolveClassNameFromPropertyInfo();

        if ($className === null) {
            $this->resolveFromReflection($this->entityClass);
            return;
        }

        $this->targetClass = $className;
        $this->isEnum = enum_exists($className);
    }

    private function resolveClassNameFromPropertyInfo(): ?string
    {
        // Symfony 8+: getType() returns a TypeInfo\Type
        if (method_exists($this->propertyInfo, 'getType')) {
            $type = $this->propertyInfo->getType($this->entityClass, $this->property);

            if ($type instanceof NullableType) {
                $type = $type->getWrappedType();
            }

            if ($type instanceof ObjectType) {
                return $type->getClassName();
            }

            return null;
        }

        // Symfony 6.4/7.x: getTypes() returns PropertyInfo\Type[]
        /** @phpstan-ignore method.notFound */
        $types = $this->propertyInfo->getTypes($this->entityClass, $this->property);

        if ($types === null || $types === []) {
            return null;
        }

        return $types[0]->getClassName();
    }

    private function resolveFromReflection(string $entityClass): void
    {
        $reflectionClass = new \ReflectionClass($entityClass);
        if (!$reflectionClass->hasProperty($this->property)) {
            return;
        }

        $reflectionProperty = $reflectionClass->getProperty($this->property);
        $type = $reflectionProperty->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $this->targetClass = $type->getName();
            $this->isEnum = enum_exists($this->targetClass);
        }
    }

    private function initializeValue(): void
    {
        $currentValue = $this->propertyAccessor->getValue($this->getEntity(), $this->property);

        if ($currentValue === null) {
            $this->value = null;
        } elseif ($currentValue instanceof BackedEnum) {
            $this->value = (string) $currentValue->value;
        } else {
            $this->value = (string) $this->propertyAccessor->getValue($currentValue, $this->valueProperty);
        }
    }
}
