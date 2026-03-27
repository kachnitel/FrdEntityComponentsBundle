<?php

namespace Kachnitel\EntityComponentsBundle\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;

/**
 * @template T of AttachmentInterface
 */
trait AttachableTrait
{
    /** @var Collection<int, T> $attachments */
    private Collection $attachments;

    private function initializeAttachments(): void
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * @return Collection<int, T>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * @param T $attachment
     */
    public function addAttachment(AttachmentInterface $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * @param T $attachment
     */
    public function removeAttachment(AttachmentInterface $attachment): static
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }
}
