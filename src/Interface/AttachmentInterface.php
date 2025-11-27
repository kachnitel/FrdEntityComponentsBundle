<?php

namespace Frd\EntityComponentsBundle\Interface;

interface AttachmentInterface
{
    public function getId(): ?int;

    public function getUrl(): ?string;

    public function getMimeType(): ?string;

    public function getPath(): ?string;
}
