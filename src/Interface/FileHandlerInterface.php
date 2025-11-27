<?php

namespace Frd\EntityComponentsBundle\Interface;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileHandlerInterface
{
    /**
     * Handle file upload and return the created attachment entity
     */
    public function handle(UploadedFile $file): AttachmentInterface;

    /**
     * Delete a file from storage
     */
    public function deleteFile(AttachmentInterface $attachment): void;
}
