<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Components\Field;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Base class for all inline-editable field LiveComponents.
 *
 * ## Why entityClass + entityId instead of an entity LiveProp
 *
 * LiveComponent serializes all LiveProps to JSON for the data-live-props-value HTML attribute.
 * Entity objects cannot survive this round-trip (proxies, circular references). Storing the FQCN
 * and integer PK as scalar LiveProps avoids this; the entity is re-fetched on each request.
 *
 * ## LiveProp constraints
 *
 * - $entityClass, $entityId, $property  MUST NOT be nullable (always known)
 * - All LiveProps use a single concrete type (no union types)
 *
 * ## Editability policy
 *
 * Delegated entirely to EditabilityResolverInterface. The default implementation
 * ({@see DefaultEditabilityResolver}) allows editing any writable property.
 * Replace the service binding to enforce custom policy (e.g. voter checks, attribute flags).
 *
 * ## save() / persistEdit() — template method pattern
 *
 * The base save() method owns the full lifecycle in this order:
 *
 *   1. canEdit() guard    — throws RuntimeException on access denied (BEFORE any mutation)
 *   2. persistEdit()      — subclass writes the new value to the entity
 *   3. validation         — ValidatorInterface::validateProperty() runs on the modified entity.
 *                           If violations exist, $errorMessage is set and the entity is refreshed.
 *                           Returns early — no flush.
 *   4. flush()            — persist to DB only when validation passes
 *   5. editMode = false   — exit edit mode
 *   6. saveSuccess = true — signal a successful save for template display
 *
 * Subclasses MUST override persistEdit() instead of save() to write their value.
 *
 * ## cancelEdit pattern
 *
 * Subclasses override cancelEdit() and re-read the property value from the entity
 * AFTER calling parent::cancelEdit():
 *
 * ```php
 * public function cancelEdit(): void
 * {
 *     parent::cancelEdit();                     // refreshes entity; clears resolvedEntity cache
 *     $raw = $this->readValue();                // reads from the now-refreshed entity
 *     $this->currentValue = $raw !== null ? (string) $raw : null;
 * }
 * ```
 *
 * Always call parent FIRST so that EntityManager::refresh() runs before you read the persisted value.
 */
abstract class AbstractEditableField
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public bool $editMode = false;

    /** Fully-qualified entity class name. Non-nullable. */
    #[LiveProp]
    public string $entityClass = '';

    /** Integer primary key. Non-nullable. */
    #[LiveProp]
    public int $entityId = 0;

    /** Property name on the entity. Non-nullable. */
    #[LiveProp]
    public string $property = '';

    /**
     * Validation error from the most recent failed save.
     * Cleared on activateEditing() and cancelEdit().
     */
    #[LiveProp]
    public string $errorMessage = '';

    /**
     * Set to true after a successful flush, reset on the next activateEditing().
     * Templates use this to show a brief "✓ Saved" indicator in display mode.
     */
    #[LiveProp]
    public bool $saveSuccess = false;

    #[ExposeInTemplate('entity')]
    public ?object $resolvedEntity = null;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EditabilityResolverInterface $editabilityResolver,
        private readonly ValidatorInterface $validator,
    ) {}

    /**
     * Populate scalar LiveProps from the entity on the initial (mount) render.
     * Not called on LiveComponent re-renders — those go through hydrateWith instead.
     *
     * @throws \InvalidArgumentException
     */
    public function mount(object $entity, string $property): void
    {
        $realClass = $entity::class;
        if (str_contains($realClass, 'Proxies\\__CG__\\')) {
            $parent    = get_parent_class($entity);
            $realClass = $parent !== false ? $parent : $realClass;
        }

        if (!method_exists($entity, 'getId')) {
            throw new \InvalidArgumentException(
                "Entity {$realClass} must have a getId() method for inline editing."
            );
        }

        $id = $entity->getId();
        if (!is_int($id)) {
            throw new \InvalidArgumentException(
                "getId() on {$realClass} must return int. Got: " . get_debug_type($id)
            );
        }

        $this->entityClass    = $realClass;
        $this->entityId       = $id;
        $this->property       = $property;
        $this->resolvedEntity = $entity;
    }

    /**
     * Re-populate resolvedEntity after LiveProps are hydrated on re-renders.
     * mount() is not called on subsequent LiveComponent requests.
     */
    #[PostHydrate]
    public function initResolvedEntity(): void
    {
        if ($this->entityClass !== '' && $this->entityId !== 0) {
            $this->getEntity();
        }
    }

    /** @throws \RuntimeException */
    public function getEntity(): object
    {
        if ($this->resolvedEntity !== null) {
            return $this->resolvedEntity;
        }

        /** @var class-string $class */
        $class  = $this->entityClass;
        $entity = $this->entityManager->find($class, $this->entityId);

        if ($entity === null) {
            throw new \RuntimeException("Entity {$this->entityClass}#{$this->entityId} not found.");
        }

        return $this->resolvedEntity = $entity;
    }

    #[ExposeInTemplate]
    public function getEntityShortClass(): string
    {
        if ($this->entityClass === '') {
            return '';
        }
        $parts = explode('\\', $this->entityClass);

        return end($parts);
    }

    #[ExposeInTemplate('value')]
    public function readValue(): mixed
    {
        return $this->propertyAccessor->getValue($this->getEntity(), $this->property);
    }

    protected function writeValue(mixed $value): void
    {
        $entity = $this->getEntity();
        $this->propertyAccessor->setValue($entity, $this->property, $value);
    }

    /**
     * Whether the current user may edit this field.
     *
     * Delegates to EditabilityResolverInterface. Override the resolver binding in
     * services.yaml to implement custom policy (roles, entity state, etc.).
     */
    #[ExposeInTemplate]
    public function canEdit(): bool
    {
        if ($this->entityClass === '' || $this->entityId === 0) {
            return false;
        }

        return $this->editabilityResolver->canEdit($this->getEntity(), $this->property);
    }

    // ── LiveActions ────────────────────────────────────────────────────────────

    #[LiveAction]
    public function activateEditing(): void
    {
        if ($this->canEdit()) {
            $this->editMode     = true;
            $this->errorMessage = '';
            $this->saveSuccess  = false;
        }
    }

    /**
     * Exit edit mode and discard unsaved input by refreshing the entity from the database.
     *
     * ## Subclass contract
     *
     * Subclasses that hold an additional LiveProp representing the user's in-progress edit
     * MUST override this method and re-read the property value AFTER calling parent::cancelEdit():
     *
     * ```php
     * public function cancelEdit(): void
     * {
     *     parent::cancelEdit();
     *     $raw = $this->readValue();
     *     $this->currentValue = $raw !== null ? (string) $raw : null;
     * }
     * ```
     */
    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editMode     = false;
        $this->errorMessage = '';

        $this->entityManager->refresh($this->getEntity());
    }

    /**
     * Subclasses override this to write their value to the entity.
     *
     * Called only after canEdit() passes in the base save() method.
     * Use writeValue() or direct adder/remover calls to persist the change.
     */
    protected function persistEdit(): void {}

    /**
     * Guard → write → validate → flush.
     *
     * This is the #[LiveAction] entry point. It calls persistEdit() — which subclasses
     * override — only after verifying canEdit(). This ensures the permission check always
     * runs before any entity mutation, regardless of the subclass implementation.
     */
    #[LiveAction]
    public function save(): void
    {
        if (!$this->canEdit()) {
            throw new \RuntimeException('Access denied for editing this field.');
        }

        $this->errorMessage = '';
        $this->persistEdit();

        $errors = $this->validator->validateProperty($this->getEntity(), $this->property);
        if (count($errors) > 0) {
            $this->errorMessage = (string) $errors->get(0)->getMessage();
            $this->entityManager->refresh($this->getEntity());

            return;
        }

        $this->entityManager->flush();
        $this->editMode    = false;
        $this->saveSuccess = true;
    }

    // ── Utilities ──────────────────────────────────────────────────────────────

    /**
     * Human-readable label derived from the property name.
     * "createdAt" → "Created At",  "firstName" → "First Name"
     */
    #[ExposeInTemplate]
    public function getLabel(): string
    {
        $words = preg_replace('/([A-Z])/', ' $1', $this->property) ?? $this->property;

        return ucfirst(trim($words));
    }
}
