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
 * loads appropriate options. All display and access configuration is
 * passed as a plain array via mount() and internally converted to a
 * {@see SelectRelationshipOptions} DTO.
 *
 * ```twig
 * <twig:K:Entity:SelectRelationship
 *     :entity="order"
 *     property="region"
 *     :config="{ placeholder: '— Region —', role: 'ROLE_ORDER_REGION_EDIT' }"
 * />
 * ```
 */
#[AsLiveComponent('K:Entity:SelectRelationship', template: '@KachnitelEntityComponents/components/SelectRelationship.html.twig')]
final class SelectRelationship
{
    use DefaultActionTrait;

    /** Stored as string to avoid LiveProp union type limitation */
    #[LiveProp(writable: true, onUpdated: 'onValueChanged')]
    public ?string $value = null;

    #[LiveProp]
    public string $entityClass = '';

    #[LiveProp]
    public ?string $entityId = null;

    #[LiveProp]
    public string $property = '';

    #[LiveProp(hydrateWith: 'hydrateOptions', dehydrateWith: 'dehydrateOptions')]
    public SelectRelationshipOptions $config;

    private ?string $targetClass = null;
    private bool $isEnum = false;
    private ?object $entity = null;

    public function __construct(
        private EntityManagerInterface $em,
        private PropertyInfoExtractorInterface $propertyInfo,
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->config = new SelectRelationshipOptions();
    }

    /**
     * @param array<string, mixed> $config Keys must match {@see SelectRelationshipOptions} constructor parameters.
     *
     * Twig usage:
     * ```twig
     * <twig:K:Entity:SelectRelationship
     *     :entity="order"
     *     property="region"
     *     :config="{ placeholder: '— Region —', role: 'ROLE_EDITOR' }"
     * />
     * ```
     */
    public function mount(
        object $entity,
        string $property,
        array $config = [],
    ): void {
        $this->entityClass = get_class($entity);
        $this->entityId    = (string) $this->propertyAccessor->getValue($entity, 'id');
        $this->property    = $property;
        $this->config     = new SelectRelationshipOptions(...$config);
        $this->entity      = $entity;

        $this->resolveTargetType();
        $this->initializeValue();
    }

    // ── LiveProp hydration ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateOptions(array $data): SelectRelationshipOptions
    {
        return new SelectRelationshipOptions(
            placeholder:      (string) ($data['placeholder'] ?? '-'),
            valueProperty:    (string) ($data['valueProperty'] ?? 'id'),
            displayProperty:  (string) ($data['displayProperty'] ?? 'name'),
            disableEmpty:     (bool) ($data['disableEmpty'] ?? false),
            filter:           (array) ($data['filter'] ?? []),
            repositoryMethod: isset($data['repositoryMethod']) ? (string) $data['repositoryMethod'] : null,
            repositoryArgs:   (array) ($data['repositoryArgs'] ?? []),
            role:             isset($data['role']) ? (string) $data['role'] : null,
            viewRole:         isset($data['viewRole']) ? (string) $data['viewRole'] : null,
            disabled:         (bool) ($data['disabled'] ?? false),
            label:            isset($data['label']) ? (string) $data['label'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function dehydrateOptions(SelectRelationshipOptions $options): array
    {
        return [
            'placeholder'      => $options->placeholder,
            'valueProperty'    => $options->valueProperty,
            'displayProperty'  => $options->displayProperty,
            'disableEmpty'     => $options->disableEmpty,
            'filter'           => $options->filter,
            'repositoryMethod' => $options->repositoryMethod,
            'repositoryArgs'   => $options->repositoryArgs,
            'role'             => $options->role,
            'viewRole'         => $options->viewRole,
            'disabled'         => $options->disabled,
            'label'            => $options->label,
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────────

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

    // ── Template helpers ──────────────────────────────────────────────────────

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

        if ($this->config->repositoryMethod !== null) {
            return $repository->{$this->config->repositoryMethod}(...$this->config->repositoryArgs);
        }

        if (!empty($this->config->filter)) {
            return $repository->findBy($this->config->filter);
        }

        return $repository->findAll();
    }

    public function getOptionValue(object $option): string
    {
        if ($option instanceof BackedEnum) {
            return (string) $option->value;
        }

        return (string) $this->propertyAccessor->getValue($option, $this->config->valueProperty);
    }

    public function getOptionLabel(object $option): string
    {
        if ($option instanceof BackedEnum) {
            if (method_exists($option, 'displayValue')) {
                return $option->displayValue();
            }

            return $option->name;
        }

        return (string) $this->propertyAccessor->getValue($option, $this->config->displayProperty);
    }

    public function isSelected(object $option): bool
    {
        return $this->getOptionValue($option) == $this->value;
    }

    public function getCurrentDisplayValue(): string
    {
        $currentValue = $this->propertyAccessor->getValue($this->getEntity(), $this->property);

        if ($currentValue === null) {
            return $this->config->placeholder;
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

    /** Expose entity for template (e.g. entity.isDeleted checks) */
    public function getEntityObject(): object
    {
        return $this->getEntity();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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
        $this->isEnum      = enum_exists($className);
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
        $type               = $reflectionProperty->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            $this->targetClass = $type->getName();
            $this->isEnum      = enum_exists($this->targetClass);
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
            $this->value = (string) $this->propertyAccessor->getValue($currentValue, $this->config->valueProperty);
        }
    }
}
