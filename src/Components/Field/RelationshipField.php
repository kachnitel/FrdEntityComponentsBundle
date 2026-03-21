<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Inline-editable field for ManyToOne and OneToOne relationships.
 *
 * Uses live search to find related entities. Stores only the related entity's
 * identifier as a LiveProp (not the full object) to avoid serialization issues.
 *
 * ## When to use this vs SelectRelationship
 *
 * - **RelationshipField** — large option sets (100+ entities), lazy search, save/cancel lifecycle
 * - **SelectRelationship** — small option sets, eager dropdown, persists immediately on change
 *
 * ## Search field auto-detection
 *
 * Searches `name`, `label`, `title`, or `id` fields on the target entity.
 * Add `__toString()` or a named priority field to the target entity for a better label.
 *
 * @example
 * ```twig
 * <twig:K:Entity:Field:Relationship :entity="product" property="category" />
 * ```
 */
#[AsLiveComponent('K:Entity:Field:Relationship', template: '@KachnitelEntityComponents/components/field/RelationshipField.html.twig')]
final class RelationshipField extends AbstractEditableField
{
    use Traits\PropertyInfoTrait;
    use Traits\AssociationFieldTrait;

    /** ID of the currently selected related entity, or null for an empty relationship. */
    #[LiveProp(writable: true)]
    public ?int $selectedId = null;

    public function mount(object $entity, string $property): void
    {
        parent::mount($entity, $property);

        $value           = $this->readValue();
        $this->selectedId = ($value !== null && method_exists($value, 'getId'))
            ? $value->getId()
            : null;
    }

    // ── Display helpers ────────────────────────────────────────────────────────

    /**
     * Label for the currently selected entity (shown in the edit-mode selected pill).
     * Returns null when no entity is selected.
     */
    #[ExposeInTemplate]
    public function getSelectedLabel(): ?string
    {
        if ($this->selectedId === null) {
            return null;
        }

        $targetClass = $this->getTargetEntityClass();
        if ($targetClass === null) {
            return "#{$this->selectedId}";
        }

        $entity = $this->entityManager->find($targetClass, $this->selectedId);

        return $entity !== null ? $this->resolveLabel($entity) : "#{$this->selectedId}";
    }

    // ── LiveActions ────────────────────────────────────────────────────────────

    /**
     * Select a related entity from the search results dropdown.
     * Clears the search query so the dropdown collapses on re-render.
     */
    #[LiveAction]
    public function select(#[LiveArg] int $id): void
    {
        $this->selectedId  = $id;
        $this->searchQuery = '';
    }

    /**
     * Clear the current selection (set relationship to null).
     */
    #[LiveAction]
    public function clear(): void
    {
        $this->selectedId  = null;
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        parent::cancelEdit();

        $value            = $this->readValue();
        $this->selectedId = ($value !== null && method_exists($value, 'getId'))
            ? $value->getId()
            : null;
        $this->searchQuery = '';
    }

    // ── Template method ────────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException when the property is not a recognised Doctrine association
     *                           or when the selected entity cannot be found
     */
    protected function persistEdit(): void
    {
        $newValue = null;

        if ($this->selectedId !== null) {
            $targetClass = $this->getTargetEntityClass();

            if ($targetClass === null) {
                throw new \RuntimeException(sprintf(
                    '"%s::$%s" is not a recognised Doctrine association.',
                    $this->entityClass,
                    $this->property,
                ));
            }

            $newValue = $this->entityManager->find($targetClass, $this->selectedId);

            if ($newValue === null) {
                throw new \RuntimeException(
                    "Entity {$targetClass} with id {$this->selectedId} not found."
                );
            }
        }

        $this->writeValue($newValue);
    }
}
