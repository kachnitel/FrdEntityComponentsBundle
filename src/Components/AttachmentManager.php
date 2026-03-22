<?php

namespace Kachnitel\EntityComponentsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Kachnitel\EntityComponentsBundle\Interface\AttachableInterface;
use Kachnitel\EntityComponentsBundle\Interface\AttachmentInterface;
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;
use Kachnitel\EntityComponentsBundle\Trait\EntityLiveComponentTrait;
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

/**
 * Live Component for managing file attachments on any AttachableInterface entity.
 *
 * The coupling count (CBO) of this class is inherently high because it integrates
 * Doctrine, Symfony Form, LiveComponent, file handling, and HTTP — all of which are
 * required to deliver a self-contained upload/delete UI component. Each dependency
 * serves a distinct, non-removable role in the component's feature set.
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 */
#[AsLiveComponent('K:Entity:AttachmentManager', template: '@KachnitelEntityComponents/components/AttachmentManager.html.twig')]
final class AttachmentManager extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;
    use EntityLiveComponentTrait;

    #[LiveProp]
    public string $attachmentClass;

    #[LiveProp(hydrateWith: 'hydrateOptions', dehydrateWith: 'dehydrateOptions')]
    public AttachmentManagerOptions $options;

    public array $errors = [];

    #[ExposeInTemplate(getter: 'getEntity')]
    private ?AttachableInterface $entity = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private readonly FileHandlerInterface $fileHandler
    ) {
        $this->options = new AttachmentManagerOptions();
    }

    public function mount(
        AttachableInterface $entity,
        string $attachmentClass,
        AttachmentManagerOptions $options = new AttachmentManagerOptions(),
    ): void {
        if (!is_a($attachmentClass, AttachmentInterface::class, true)) {
            throw new \InvalidArgumentException("Attachment class must implement AttachmentInterface. {$attachmentClass} does not.");
        }

        $this->mountEntity($entity);
        $this->attachmentClass = $attachmentClass;
        $this->options         = $options;
        $this->entity          = $entity;
    }

    // ── LiveProp hydration ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateOptions(array $data): AttachmentManagerOptions
    {
        return new AttachmentManagerOptions(
            readOnly: (bool) ($data['readOnly'] ?? false),
            property: (string) ($data['property'] ?? 'attachments'),
            tagClass: isset($data['tagClass']) ? (string) $data['tagClass'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function dehydrateOptions(AttachmentManagerOptions $options): array
    {
        return [
            'readOnly' => $options->readOnly,
            'property' => $options->property,
            'tagClass' => $options->tagClass,
        ];
    }

    // ── Entity access ─────────────────────────────────────────────────────────

    public function getEntity(): AttachableInterface
    {
        if (!$this->entity) {
            $this->entity = $this->loadEntity(AttachableInterface::class);
        }

        return $this->entity;
    }

    #[ExposeInTemplate('attachments')]
    public function getAttachments(): array
    {
        $entity = $this->getEntity();
        $method = 'get' . ucfirst($this->options->property);

        if (!method_exists($entity, $method)) {
            throw new Exception(sprintf(
                'Method "%s" does not exist on entity "%s"',
                $method,
                get_class($entity)
            ));
        }

        $attachments = $entity->$method();

        if (!$attachments instanceof \Doctrine\Common\Collections\Collection) {
            throw new Exception(sprintf(
                'Property "%s" is not a Collection on entity "%s"',
                $this->options->property,
                get_class($entity)
            ));
        }

        return $attachments->getValues();
    }

    protected function instantiateForm(): FormInterface
    {
        $entity = $this->entityManager->getRepository($this->entityClass)->find($this->entityId);

        return $this->createFormBuilder($entity)
            ->add($this->options->property, CollectionType::class, [
                'label'          => 'Upload Files',
                'entry_type'     => FileType::class,
                'entry_options'  => ['label' => false],
                'allow_add'      => true,
                'allow_delete'   => true,
                'prototype_name' => '__uploaded_files__',
                'required'       => false,
                'mapped'         => false,
            ])
            ->setDisabled($this->options->readOnly)
            ->getForm();
    }

    // ── LiveActions ───────────────────────────────────────────────────────────

    #[LiveAction]
    public function addFile(): void
    {
        $this->formValues[$this->options->property][] = null;
    }

    #[LiveAction]
    public function submit(Request $request): void
    {
        if (!$request->files->get('form')) {
            $this->errors = ['No files uploaded'];

            return;
        }

        try {
            $files = $request->files->get('form')[$this->options->property] ?? [];
            $this->handleFiles($files);

            $this->entityManager->flush();

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Files uploaded successfully',
                'type'    => 'success',
            ]);

            $this->errors = [];
        } catch (Exception $e) {
            $this->errors = [$e->getMessage()];
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'caller'    => __METHOD__,
            ]);

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Error uploading files: ' . $e->getMessage(),
                'type'    => 'error',
            ]);
        }

        $this->resetForm();
    }

    protected function handleFiles(array $files): void
    {
        $entity      = $this->getEntity();
        $adderMethod = $this->getMethod('add');

        foreach ($files as $file) {
            if ($file === null) {
                continue;
            }

            $attachment = $this->fileHandler->handle($file);
            $entity->$adderMethod($attachment);
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

            $entity        = $this->getEntity();
            $removerMethod = $this->getMethod('remove');
            $entity->$removerMethod($attachment);

            $this->fileHandler->deleteFile($attachment);

            $this->entityManager->flush();

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'File deleted successfully',
                'type'    => 'success',
            ]);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'caller'    => __METHOD__,
            ]);

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Error deleting file: ' . $e->getMessage(),
                'type'    => 'error',
            ]);
        }
    }

    private function getMethod(string $prefix): string
    {
        $entity           = $this->getEntity();
        $singularProperty = rtrim($this->options->property, 's');
        $method           = $prefix . ucfirst($singularProperty);

        if (method_exists($entity, $method)) {
            return $method;
        }

        throw new Exception(sprintf(
            'Method "%s" does not exist on entity "%s"',
            $method,
            get_class($entity)
        ));
    }
}
