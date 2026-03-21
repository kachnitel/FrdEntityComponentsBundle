<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field\Traits;

use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Provides Doctrine-backed type introspection for inline-editable field components.
 *
 * Requires the using class to have $entityManager (EntityManagerInterface) and
 * $property (string) available — both provided by AbstractEditableField.
 *
 * ## Symfony version compatibility
 *
 * `DoctrineExtractor::getType()` (returning a TypeInfo `Type` object) was added in
 * Symfony 7.1. In Symfony 6.4, only `getTypes()` (returning `array<PropertyInfo\Type>`)
 * is available. The private `resolveDoctrineType()` helper uses `method_exists()` to
 * pick the right path at runtime.
 */
trait PropertyInfoTrait
{
    private DoctrineExtractor $doctrineExtractor;
    private ReflectionExtractor $reflectionExtractor;

    /**
     * Injects the ReflectionExtractor from the container when symfony/property-info is enabled.
     *
     * Falls back to a new ReflectionExtractor() with the default EnglishInflector when the
     * service is not registered. The only observable difference is that apps with a custom
     * inflector configured on the container service will silently get English inflection instead.
     *
     * @note If you observe inflection mismatches (e.g. addCategory not found for $categories),
     *       ensure framework.property_info is enabled in your Symfony configuration.
     */
    #[Required]
    public function initPropertyInfoExtractors(?ReflectionExtractor $reflectionExtractor = null): void
    {
        $this->doctrineExtractor   = new DoctrineExtractor($this->entityManager);
        $this->reflectionExtractor = $reflectionExtractor ?? new ReflectionExtractor();
    }

    protected function getPropertyType(): ?string
    {
        return $this->resolveDoctrineType()['typeString'] ?? null;
    }

    protected function isRequired(): bool
    {
        $info = $this->resolveDoctrineType();

        return $info !== null
            && !$info['nullable']
            && $this->propertyAccessor->isWritable($this->getEntity(), $this->property);
    }

    protected function isNullable(): bool
    {
        $info = $this->resolveDoctrineType();

        return $info !== null && $info['nullable'];
    }

    /**
     * Resolve the target entity class for any association type (ManyToOne, OneToOne,
     * OneToMany, ManyToMany). Returns null when the property is not a Doctrine association.
     *
     * @return class-string|null
     */
    protected function getTargetEntityClass(): ?string
    {
        $metadata = $this->entityManager->getClassMetadata($this->entityClass);

        if (!$metadata->hasAssociation($this->property)) {
            return null;
        }

        /** @var class-string */
        return $metadata->getAssociationTargetClass($this->property);
    }

    /**
     * Resolve the adder and remover method names for a collection-valued association
     * using Symfony's ReflectionExtractor and its built-in EnglishInflector.
     *
     * The inflector correctly singularises common English plurals:
     *   $categories → addCategory / removeCategory
     *   $tags       → addTag / removeTag
     *
     * @return array{adder: string, remover: string}
     *
     * @throws \RuntimeException when no adder/remover pair can be found
     */
    protected function getCollectionMutators(): array
    {
        $writeInfo = $this->reflectionExtractor->getWriteInfo($this->entityClass, $this->property);

        if ($writeInfo === null || $writeInfo->getType() !== PropertyWriteInfo::TYPE_ADDER_AND_REMOVER) {
            throw new \RuntimeException(sprintf(
                'Cannot mutate collection "%s::$%s": no adder/remover pair found. '
                . 'Add addX()/removeX() methods to your entity, or make the property directly writable.',
                $this->entityClass,
                $this->property,
            ));
        }

        return [
            'adder'   => $writeInfo->getAdderInfo()->getName(),
            'remover' => $writeInfo->getRemoverInfo()->getName(),
        ];
    }

    // ── Symfony version compatibility ─────────────────────────────────────────

    /**
     * Normalize Doctrine property type info across Symfony versions.
     *
     * @return array{nullable: bool, typeString: ?string}|null null when no type info available
     */
    private function resolveDoctrineType(): ?array
    {
        /** @phpstan-ignore function.alreadyNarrowedType */
        if (method_exists($this->doctrineExtractor, 'getType')) {
            // Symfony 7.1+
            $type = $this->doctrineExtractor->getType($this->entityClass, $this->property);
            if ($type === null) {
                return null;
            }

            return ['nullable' => $type->isNullable(), 'typeString' => $type->__toString()];
        }

        // Symfony 6.4
        /** @phpstan-ignore method.notFound */
        $types = $this->doctrineExtractor->getTypes($this->entityClass, $this->property);
        if ($types === null || $types === []) {
            return null;
        }

        $first    = $types[0];
        $nullable = false;
        foreach ($types as $t) {
            if ($t->isNullable()) {
                $nullable = true;
                break;
            }
        }

        $typeString = $first->getBuiltinType() === 'object'
            ? $first->getClassName()
            : $first->getBuiltinType();

        return ['nullable' => $nullable, 'typeString' => $typeString];
    }
}
