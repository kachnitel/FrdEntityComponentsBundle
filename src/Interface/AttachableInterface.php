<?php

namespace Frd\EntityComponentsBundle\Interface;

use Doctrine\Common\Collections\Collection;

interface AttachableInterface
{
    /**
     * @return Collection<int, AttachmentInterface>
     */
    public function getAttachments(): Collection;

    public function addAttachment(AttachmentInterface $attachment): self;

    public function removeAttachment(AttachmentInterface $attachment): self;
}
