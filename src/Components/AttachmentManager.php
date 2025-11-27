<?php

namespace Frd\EntityComponentsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Frd\EntityComponentsBundle\Interface\AttachableInterface;
use Frd\EntityComponentsBundle\Interface\AttachmentInterface;
use Frd\EntityComponentsBundle\Interface\FileHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('FRD:Entity:AttachmentManager', template: '@FrdEntityComponents/components/AttachmentManager.html.twig')]
final class AttachmentManager extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public AttachableInterface $entity;

    #[LiveProp]
    public string $attachmentClass;

    #[LiveProp]
    public bool $readOnly = false;

    #[LiveProp]
    public string $property = 'attachments';

    public array $errors = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileHandlerInterface $fileHandler,
        private LoggerInterface $logger
    ) {}

    #[ExposeInTemplate('attachments')]
    public function getAttachments(): array
    {
        $method = 'get' . ucfirst($this->property);

        if (!method_exists($this->entity, $method)) {
            throw new Exception(sprintf(
                'Method "%s" does not exist on entity "%s"',
                $method,
                get_class($this->entity)
            ));
        }

        $attachments = $this->entity->$method();

        if (!$attachments instanceof \Doctrine\Common\Collections\Collection) {
            throw new Exception(sprintf(
                'Property "%s" is not a Collection on entity "%s"',
                $this->property,
                get_class($this->entity)
            ));
        }

        return $attachments->getValues();
    }

    protected function instantiateForm(): FormInterface
    {
        // Get the actual entity class (handle Doctrine proxies)
        $entityClass = get_class($this->entity);
        if (str_contains($entityClass, 'Proxies\\__CG__\\')) {
            $entityClass = get_parent_class($this->entity);
        }

        $entity = $this->entityManager->getRepository($entityClass)->find($this->entity->getId());

        return $this->createFormBuilder($entity)
            ->add($this->property, CollectionType::class, [
                'label' => 'Upload Files',
                'entry_type' => FileType::class,
                'entry_options' => [
                    'label' => false
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype_name' => '__uploaded_files__',
                'required' => false,
                'mapped' => false
            ])
            ->setDisabled($this->readOnly)
            ->getForm();
    }

    #[LiveAction]
    public function addFile(): void
    {
        $this->formValues[$this->property][] = null;
    }

    #[LiveAction]
    public function submit(Request $request): void
    {
        if (!$request->files->get('form')) {
            $this->errors = ['No files uploaded'];
            return;
        }

        try {
            $files = $request->files->get('form')[$this->property] ?? [];
            $this->handleFiles($files);

            $this->entityManager->flush();

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Files uploaded successfully',
                'type' => 'success'
            ]);

            $this->errors = [];
        } catch (Exception $e) {
            $this->errors = [$e->getMessage()];
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'caller' => __METHOD__,
            ]);

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Error uploading files: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }

        $this->resetForm();
    }

    protected function handleFiles(array $files): void
    {
        $adderMethod = $this->getMethod('add');

        foreach ($files as $file) {
            if ($file === null) {
                continue;
            }

            $attachment = $this->fileHandler->handle($file);
            $this->entity->$adderMethod($attachment);
        }
    }

    #[LiveAction]
    public function deleteFile(#[LiveArg] int $id, #[LiveArg] string $csrfToken): void
    {
        if (!$this->isCsrfTokenValid('delete-file-' . $id, $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        try {
            /** @var AttachmentInterface $attachment */
            $attachment = $this->entityManager
                ->getRepository($this->attachmentClass)
                ->find($id);

            if (!$attachment) {
                throw new Exception('Attachment not found');
            }

            $removerMethod = $this->getMethod('remove');
            $this->entity->$removerMethod($attachment);

            $this->fileHandler->deleteFile($attachment);

            $this->entityManager->flush();

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'File deleted successfully',
                'type' => 'success'
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'caller' => __METHOD__,
            ]);

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Error deleting file: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    private function getMethod(string $prefix): string
    {
        // Try standard naming: addAttachment, removeAttachment
        $singularProperty = rtrim($this->property, 's');
        $method = $prefix . ucfirst($singularProperty);

        if (method_exists($this->entity, $method)) {
            return $method;
        }

        throw new Exception(sprintf(
            'Method "%s" does not exist on entity "%s"',
            $method,
            get_class($this->entity)
        ));
    }
}
