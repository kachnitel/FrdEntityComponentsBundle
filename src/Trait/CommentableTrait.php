<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kachnitel\EntityComponentsBundle\Interface\CommentInterface;

/**
 * Provides a ManyToMany comments collection for any entity.
 *
 * The Doctrine mapping targets {@see CommentInterface}. Doctrine resolves the
 * concrete class at runtime via `resolve_target_entities` in your app's
 * `doctrine.yaml`. The bundle's {@see JoinTableNormalizerSubscriber} rewrites
 * the auto-generated join table name to use your concrete class name:
 *   article_comment_interface → article_comment
 *
 * ## Setup
 *
 * 1. Configure `resolve_target_entities` in `config/packages/doctrine.yaml`:
 *
 * ```yaml
 * doctrine:
 *     orm:
 *         resolve_target_entities:
 *             Kachnitel\EntityComponentsBundle\Interface\CommentInterface: App\Entity\Comment
 * ```
 *
 * 2. Use the trait and call `initializeComments()` in your constructor:
 *
 * ```php
 * class Article implements CommentableInterface
 * {
 *     use CommentableTrait;
 *
 *     public function __construct() { $this->initializeComments(); }
 * }
 * ```
 *
 * ## Uniqueness
 *
 * The inverse join column carries `unique: true`, meaning each comment belongs
 * to exactly one parent entity. A comment has no meaningful existence outside
 * its owner — author attribution, threading, and deletion all assume a single
 * parent.
 *
 * ## Cascade
 *
 * Comments are cascade-persisted and cascade-removed. Override the mapping in
 * your entity if you need different behaviour:
 *
 * ```php
 * #[ORM\ManyToMany(targetEntity: CommentInterface::class, cascade: [])]
 * #[ORM\JoinTable(name: 'article_notes')]
 * private Collection $comments;
 * ```
 *
 */
trait CommentableTrait
{
    /**
     * @var Collection<int, CommentInterface>
     */
    #[ORM\ManyToMany(targetEntity: CommentInterface::class, cascade: ['persist', 'remove'])]
    #[ORM\InverseJoinColumn(unique: true)]
    private Collection $comments;

    private function initializeComments(): void
    {
        $this->comments = new ArrayCollection();
    }

    /**
     * @return Collection<int, CommentInterface>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(CommentInterface $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }

        return $this;
    }

    public function removeComment(CommentInterface $comment): static
    {
        $this->comments->removeElement($comment);

        return $this;
    }
}
