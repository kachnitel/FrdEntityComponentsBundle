<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag entity for CollectionField tests.
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_test_tag')]
class FieldTestTagEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name = '';

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

/**
 * Owner entity with a ManyToOne relationship and a ManyToMany collection —
 * covers both RelationshipField and CollectionField in a single fixture.
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_test_owner')]
class FieldTestOwnerEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\ManyToOne(targetEntity: FieldTestTagEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?FieldTestTagEntity $primaryTag = null;

    /** @var Collection<int, FieldTestTagEntity> */
    #[ORM\ManyToMany(targetEntity: FieldTestTagEntity::class)]
    #[ORM\JoinTable(name: 'field_test_owner_tags')]
    private Collection $tags;

    public function __construct(string $title = '')
    {
        $this->title = $title;
        $this->tags  = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrimaryTag(): ?FieldTestTagEntity
    {
        return $this->primaryTag;
    }

    public function setPrimaryTag(?FieldTestTagEntity $primaryTag): self
    {
        $this->primaryTag = $primaryTag;

        return $this;
    }

    /** @return Collection<int, FieldTestTagEntity> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(FieldTestTagEntity $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(FieldTestTagEntity $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
