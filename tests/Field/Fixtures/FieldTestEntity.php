<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures;

use Doctrine\ORM\Mapping as ORM;

/**
 * Minimal entity covering all scalar field types used by the field component tests.
 *
 * Uses an in-memory SQLite database — no migration required.
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_test_entity')]
class FieldTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $count = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $active = null;

    #[ORM\Column(type: 'string', enumType: FieldTestStatus::class, nullable: true)]
    private ?FieldTestStatus $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getStatus(): ?FieldTestStatus
    {
        return $this->status;
    }

    public function setStatus(?FieldTestStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
