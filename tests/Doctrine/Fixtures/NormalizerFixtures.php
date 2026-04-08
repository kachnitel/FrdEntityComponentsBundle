<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Doctrine\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;
use Kachnitel\EntityComponentsBundle\Interface\CommentInterface;
use Kachnitel\EntityComponentsBundle\Interface\CommentableInterface;
use Kachnitel\EntityComponentsBundle\Interface\TaggableInterface;
use Kachnitel\EntityComponentsBundle\Interface\TagInterface;
use Kachnitel\EntityComponentsBundle\Trait\AttachableTrait;
use Kachnitel\EntityComponentsBundle\Trait\CommentableTrait;
use Kachnitel\EntityComponentsBundle\Trait\TaggableTrait;

// ── Concrete classes that resolve_target_entities will map interfaces to ──────

/**
 * Concrete Tag — maps to TagInterface.
 * Short name "NormalizerTestTag" → snake "normalizer_test_tag".
 * Expected join table: article_normalizer_test_tag
 */
#[ORM\Entity]
// #[ORM\Table(name: 'normalizer_test_tag')]
class NormalizerTestTag implements TagInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    public function getId(): ?int { return $this->id; }
    public function getValue(): ?string { return null; }
    public function getDisplayName(): ?string { return null; }
    public function getCategory(): ?string { return null; }
    public function getCategoryColor(): string { return 'cccccc'; }
}

/**
 * Concrete Attachment — maps to AttachmentInterface.
 * Short name "NormalizerTestAttachment" → snake "normalizer_test_attachment".
 * Expected join table: article_normalizer_test_attachment
 */
#[ORM\Entity]
#[ORM\Table(name: 'normalizer_test_attachment')]
class NormalizerTestAttachment implements AttachmentInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    public function getId(): ?int { return $this->id; }
    public function getUrl(): ?string { return null; }
    public function getMimeType(): ?string { return null; }
    public function getPath(): ?string { return null; }
}

/**
 * Concrete Comment — maps to CommentInterface.
 * Short name "NormalizerTestComment" → snake "normalizer_test_comment".
 * Expected join table: article_normalizer_test_comment
 */
#[ORM\Entity]
#[ORM\Table(name: 'normalizer_test_comment')]
class NormalizerTestComment implements CommentInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    public function getId(): ?int { return $this->id; }
    public function getText(): ?string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return null; }
    public function getCreatedBy(): mixed { return null; }
}

// ── Owner entity that uses all three traits ───────────────────────────────────

/**
 * Owner entity exercising all three interface-targeted ManyToMany traits.
 *
 * Each trait uses an interface as targetEntity. After JoinTableNormalizerSubscriber
 * runs, the join table names must reference the concrete class names, not the
 * interface names:
 *
 *   tags        → normalizer_article_normalizer_test_tag         (not …_tag_interface)
 *   attachments → normalizer_article_normalizer_test_attachment  (not …_attachment_interface)
 *   comments    → normalizer_article_normalizer_test_comment     (not …_comment_interface)
 *
 * @implements TaggableInterface<NormalizerTestTag>
 */
#[ORM\Entity]
#[ORM\Table(name: 'normalizer_article')]
class NormalizerArticle implements TaggableInterface, CommentableInterface
{
    /** @use TaggableTrait<NormalizerTestTag> */
    use TaggableTrait;
    /** @use AttachableTrait<NormalizerTestAttachment> */
    use AttachableTrait;
    use CommentableTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    public function __construct()
    {
        $this->initializeTags();
        $this->initializeAttachments();
        $this->initializeComments();
    }

    public function getId(): ?int { return $this->id; }
}
