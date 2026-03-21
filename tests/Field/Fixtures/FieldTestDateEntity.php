<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field\Fixtures;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * One nullable property per Doctrine date column type variant.
 * Enables every branch of DateField::getDateType() and shouldUseImmutable().
 */
#[ORM\Entity]
#[ORM\Table(name: 'field_test_date_entity')]
class FieldTestDateEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTime $birthDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $expiresOn = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?DateTime $meetingTime = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?DateTimeImmutable $loggedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getBirthDate(): ?DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?DateTime $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getExpiresOn(): ?DateTimeInterface
    {
        return $this->expiresOn;
    }

    public function setExpiresOn(?DateTimeImmutable $expiresOn): self
    {
        $this->expiresOn = $expiresOn;

        return $this;
    }

    public function getMeetingTime(): ?DateTimeInterface
    {
        return $this->meetingTime;
    }

    public function setMeetingTime(?DateTime $meetingTime): self
    {
        $this->meetingTime = $meetingTime;

        return $this;
    }

    public function getLoggedAt(): ?DateTimeInterface
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(?DateTimeImmutable $loggedAt): self
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }
}
