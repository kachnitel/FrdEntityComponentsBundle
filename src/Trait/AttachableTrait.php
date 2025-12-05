<?php

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;

trait AttachableTrait
{
    private Collection $attachments;

    private function initializeAttachments(): void
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * @return Collection<int, AttachmentInterface>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(AttachmentInterface $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this;
    }

    public function removeAttachment(AttachmentInterface $attachment): self
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }
}
