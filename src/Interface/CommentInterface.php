<?php

namespace Frd\EntityComponentsBundle\Interface;

use DateTimeImmutable;

interface CommentInterface
{
    public function getId(): ?int;
    public function getText(): ?string;
    public function setText(string $text): self;
    public function getCreatedAt(): ?DateTimeImmutable;
    public function getCreatedBy(): mixed; // Can be User or any other entity
}
