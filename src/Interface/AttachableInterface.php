<?php

namespace Kachnitel\EntityComponentsBundle\Interface;

use Doctrine\Common\Collections\Collection;

/**
 * @see Kachnitel\EntityComponentsBundle\Trait\AttachableTrait
 *
 * @template T of AttachmentInterface
 */
interface AttachableInterface
{
    /**
     * @return Collection<int, T>
     */
    public function getAttachments(): Collection;

    /**
     * @param T $attachment
     */
    public function addAttachment(AttachmentInterface $attachment): static;


    /**
     * @param T $attachment
     */
    public function removeAttachment(AttachmentInterface $attachment): static;
}
