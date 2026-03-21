<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Doctrine\Common\Collections\Collection;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Inline-editable field for collection associations (OneToMany, ManyToMany).
 *
 * Stores selected entity IDs as an array LiveProp. On save, resolves the adder/remover
 * method pair via ReflectionExtractor (respects Symfony's EnglishInflector).
 *
 * ## When to use this vs TagManager
 *
 * - **CollectionField** — generic multi-select for any collection property, part of the
 *   inline-edit field system with search, save/cancel lifecycle
 * - **TagManager** — dedicated tag UX with colored category badges, modal toggle,
 *   specific to the tagging pattern
 *
 * ## When to use this vs SelectRelationship
 *
 * - **CollectionField** — for ManyToMany/OneToMany associations (multiple selections)
 * - **SelectRelationship** — for single entity relations or enums
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:Collection :entity="post" property="categories" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:Collection', template: '@KachnitelEntityComponents/components/field/CollectionField.html.twig')]
class CollectionField extends AbstractEditableField
{
    use Traits\PropertyInfoTrait;
    use Traits\AssociationFieldTrait;

    /**
     * IDs of the entities currently selected in the edit UI.
     *
     * @var list<int>
     */
    #[LiveProp(writable: true)]
    public array $selectedIds = [];

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);
        $this->selectedIds = $this->idsFromCollection($this->readValue());
    }

    // ── Display helpers ────────────────────────────────────────────────────────

    /**
     * Resolve labels for all currently selected IDs.
     * Uses a single IN query to avoid N+1 on re-renders with many selected items.
     *
     * @return array<array{id: int, label: string}>
     */
    #[ExposeInTemplate]
    public function getSelectedItems(): array
    {
        if ($this->selectedIds === []) {
            return [];
        }

        $targetClass = $this->getTargetEntityClass();
        if ($targetClass === null) {
            return [];
        }

        /** @var object[] $entities */
        $entities = $this->entityManager
            ->getRepository($targetClass)
            ->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $this->selectedIds)
            ->getQuery()
            ->getResult();

        $entityMap = [];
        foreach ($entities as $entity) {
            if (method_exists($entity, 'getId')) {
                $id = $entity->getId();
                if (is_int($id)) {
                    $entityMap[$id] = $entity;
                }
            }
        }

        return array_map(function (int $id) use ($entityMap): array {
            return [
                'id'    => $id,
                'label' => isset($entityMap[$id]) ? $this->resolveLabel($entityMap[$id]) : "#{$id}",
            ];
        }, $this->selectedIds);
    }

    // ── LiveActions ────────────────────────────────────────────────────────────

    /**
     * Add an entity from the search results to the pending selection.
     * Prevents duplicates; clears the search query to collapse the dropdown.
     */
    #[LiveAction]
    public function addItem(#[LiveArg] int $id): void
    {
        if (!in_array($id, $this->selectedIds, true)) {
            $this->selectedIds[] = $id;
        }
        $this->searchQuery = '';
    }

    /**
     * Remove an entity from the pending selection (does not flush).
     */
    #[LiveAction]
    public function removeItem(#[LiveArg] int $id): void
    {
        $this->selectedIds = array_values(
            array_filter($this->selectedIds, fn(int $i): bool => $i !== $id)
        );
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();
        $this->selectedIds = $this->idsFromCollection($this->readValue());
        $this->searchQuery = '';
    }

    // ── Template method ────────────────────────────────────────────────────────

    /**
     * Diff selectedIds against the persisted collection and call the entity's
     * adder/remover pair for each change.
     *
     * @throws \RuntimeException when adder/remover cannot be resolved, or a related entity is missing
     */
    protected function persistEdit(): void
    {
        $targetClass = $this->requireTargetEntityClass();
        $collection  = $this->requireCollection();
        $mutators    = $this->getCollectionMutators();
        $entity      = $this->getEntity();
        $existingIds = $this->idsFromCollection($collection);

        $this->applyAdditions($entity, $targetClass, array_diff($this->selectedIds, $existingIds), $mutators['adder']);
        $this->applyRemovals($entity, $collection, array_diff($existingIds, $this->selectedIds), $mutators['remover']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @return class-string
     * @throws \RuntimeException
     */
    private function requireTargetEntityClass(): string
    {
        $targetClass = $this->getTargetEntityClass();

        if ($targetClass === null) {
            throw new \RuntimeException(sprintf(
                '"%s::$%s" is not a recognised Doctrine association.',
                $this->entityClass,
                $this->property,
            ));
        }

        return $targetClass;
    }

    /**
     * @return Collection<int, object>
     * @throws \RuntimeException
     */
    private function requireCollection(): Collection
    {
        $collection = $this->readValue();

        if (!$collection instanceof Collection) {
            throw new \RuntimeException(sprintf(
                '"%s::$%s" did not return a Doctrine Collection.',
                $this->entityClass,
                $this->property,
            ));
        }

        return $collection;
    }

    /**
     * @param class-string $targetClass
     * @param array<int>   $idsToAdd
     */
    private function applyAdditions(object $entity, string $targetClass, array $idsToAdd, string $adder): void
    {
        foreach ($idsToAdd as $id) {
            $related = $this->entityManager->find($targetClass, $id);
            if ($related === null) {
                throw new \RuntimeException("Entity {$targetClass} with id {$id} not found.");
            }
            $entity->{$adder}($related);
        }
    }

    /**
     * @param Collection<int, object> $collection
     * @param array<int>              $idsToRemove
     */
    private function applyRemovals(object $entity, Collection $collection, array $idsToRemove, string $remover): void
    {
        foreach ($idsToRemove as $id) {
            $toRemove = $this->findInCollection($collection, $id);
            if ($toRemove !== null) {
                $entity->{$remover}($toRemove);
            }
        }
    }

    /**
     * @param Collection<int, object> $collection
     */
    private function findInCollection(Collection $collection, int $id): ?object
    {
        foreach ($collection as $item) {
            if (method_exists($item, 'getId') && $item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function idsFromCollection(mixed $collection): array
    {
        if (!$collection instanceof Collection) {
            return [];
        }

        $ids = [];
        foreach ($collection as $item) {
            if (method_exists($item, 'getId')) {
                $id = $item->getId();
                if (is_int($id)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }
}
