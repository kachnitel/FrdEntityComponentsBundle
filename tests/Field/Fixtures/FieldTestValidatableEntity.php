<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity with explicit Symfony Validator constraints for testing the
 * AbstractEditableField.save() validation integration.
 *
 * - $title: required, max 20 chars
 * - $score: range 0–100
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_test_validatable_entity')]
class FieldTestValidatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $title = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

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
}
