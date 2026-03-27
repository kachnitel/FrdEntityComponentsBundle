<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Shared entity-loading behaviour for LiveComponent manager classes.
 *
 * Provides:
 *   - $entityClass / $entityId LiveProps (scalars that survive the JSON round-trip)
 *   - mountEntity(): getId() guard + Doctrine proxy class name unwrapping
 *   - loadEntity(): EM fetch with NotFoundHttpException + interface assertion
 *
 * ## Why a trait and not a base class
 *
 * AttachmentManager and CommentsManager extend AbstractController (required for
 * createFormBuilder / isCsrfTokenValid). PHP's single-inheritance model prevents
 * a shared base class; a trait is the only composition mechanism available.
 *
 * ## Caching
 *
 * loadEntity() always fetches from the EntityManager — it never caches. Each
 * component maintains its own typed private $entity property and caches there,
 * which preserves the component-level type guarantee without a generic ?object
 * property living in the trait.
 *
 * ## PHPStan
 *
 * The using class MUST inject EntityManagerInterface as $this->entityManager.
 * The @property annotation below tells PHPStan about that requirement.
 *
 * @property-read EntityManagerInterface $entityManager
 */
trait EntityLiveComponentTrait
{
    /** Fully-qualified entity class name, stripped of Doctrine proxy prefixes. */
    #[LiveProp]
    public string $entityClass = '';

    /** Integer primary key of the loaded entity. */
    #[LiveProp]
    public int $entityId = 0;

    /**
     * Populate $entityClass and $entityId from the given entity object.
     *
     * Strips Doctrine proxy class prefixes so the stored FQCN is always the
     * real application class, not a generated `Proxies\__CG__\App\Entity\Foo`.
     *
     * @throws \InvalidArgumentException when the entity has no getId() method
     */
    private function mountEntity(object $entity): void
    {
        if (!method_exists($entity, 'getId')) {
            throw new \InvalidArgumentException(
                sprintf('Entity %s must have a getId() method.', get_class($entity))
            );
        }

        $this->entityClass = $this->getRealClass($entity);
        $this->entityId    = (int) $entity->getId();
    }

    /**
     * Fetch the entity from the EntityManager and assert it implements the given interface.
     *
     * Does NOT cache — the calling component's getEntity() method is responsible
     * for caching into its own typed property.
     *
     * The template parameter T lets PHPStan propagate the concrete interface type
     * all the way to the call site, eliminating the need for a @var cast there:
     *
     * ```php
     * $this->entity = $this->loadEntity(CommentableInterface::class);
     * // PHPStan knows $this->entity is CommentableInterface — no @var needed
     * ```
     *
     * @template TInterface of object
     * @param class-string<TInterface> $interface FQCN of the interface the entity must implement.
     * @return TInterface
     *
     * @throws NotFoundHttpException     when the entity no longer exists in the database
     * @throws \InvalidArgumentException when the entity does not implement $interface
     */
    private function loadEntity(string $interface): object
    {
        /** @phpstan-ignore argument.templateType */
        $entity = $this->entityManager->getRepository($this->entityClass)->find($this->entityId);

        if (!$entity) {
            throw new NotFoundHttpException(
                "{$this->entityClass} with id {$this->entityId} not found."
            );
        }

        if (!($entity instanceof $interface)) {
            throw new \InvalidArgumentException(
                "{$this->entityClass} must implement {$interface}."
            );
        }

        /** @var TInterface $entity */
        return $entity;
    }

    /**
     * Get the real class name (unwrap Doctrine proxies).
     * REVIEW: copied from admin-bundle
     */
    private function getRealClass(string|object $subj): string
    {
        if (is_subclass_of($subj, Proxy::class, true)) {
            return get_parent_class($subj);
        }

        return $subj::class;
    }
}
