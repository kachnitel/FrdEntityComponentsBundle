<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kachnitel\EntityComponentsBundle\Interface\CommentableInterface;
use Kachnitel\EntityComponentsBundle\Interface\CommentInterface;
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;

// ── Tag ───────────────────────────────────────────────────────────────────────

#[ORM\Entity]
#[ORM\Table(name: 'comp_test_tag')]
class ComponentTestTag implements TagInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $value = '';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $category = null;

    public function __construct(string $value = '', ?string $category = null)
    {
        $this->value    = $value;
        $this->category = $category;
    }

    public function getId(): ?int { return $this->id; }

    public function getValue(): ?string { return $this->value; }

    public function getDisplayName(): ?string { return $this->value; }

    public function getCategory(): ?string { return $this->category; }

    public function getCategoryColor(): string { return 'cccccc'; }
}

// ── Taggable entity ───────────────────────────────────────────────────────────

/**
 * @implements TaggableInterface<ComponentTestTag>
 */
#[ORM\Entity]
#[ORM\Table(name: 'comp_test_taggable')]
class ComponentTestTaggableEntity implements TaggableInterface
{
    /** @use TaggableTrait<ComponentTestTag> */
    use TaggableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    /**
     * @var Collection<int, ComponentTestTag>
     */
    #[ORM\ManyToMany(targetEntity: ComponentTestTag::class)]
    #[ORM\JoinTable(name: 'comp_test_taggable_tags')]
    private Collection $tags;

    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->initializeTags();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
}

// ── Comment ───────────────────────────────────────────────────────────────────

#[ORM\Entity]
#[ORM\Table(name: 'comp_test_comment')]
class ComponentTestComment implements CommentInterface
{
    public const MAX_TEXT_LENGTH = 500;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getText(): ?string { return $this->text; }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }

    public function getCreatedBy(): mixed { return null; }
}

// ── Commentable entity ────────────────────────────────────────────────────────

/**
 * Unidirectional ManyToMany via join table — avoids requiring a back-reference
 * column on ComponentTestComment.
 */
#[ORM\Entity]
#[ORM\Table(name: 'comp_test_commentable')]
class ComponentTestCommentableEntity implements CommentableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    /**
     * Typed as CommentInterface to satisfy CommentableInterface method signatures.
     *
     * @var Collection<int, CommentInterface>
     */
    #[ORM\ManyToMany(targetEntity: ComponentTestComment::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinTable(name: 'comp_test_commentable_comments')]
    private Collection $comments;

    public function __construct(string $title = '')
    {
        $this->title    = $title;
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }

    /** @return Collection<int, CommentInterface> */
    public function getComments(): Collection { return $this->comments; }

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
