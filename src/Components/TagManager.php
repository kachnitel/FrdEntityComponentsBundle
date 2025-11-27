<?php

namespace Frd\EntityComponentsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Frd\EntityComponentsBundle\Interface\TaggableInterface;
use Frd\EntityComponentsBundle\Interface\TagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('FRD:Entity:TagManager', template: '@FrdEntityComponents/components/TagManager.html.twig')]
final class TagManager
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public int $entityId;

    #[LiveProp]
    public string $entityClass;

    #[LiveProp]
    public string $tagClass;

    #[LiveProp]
    public bool $readOnly = false;

    #[LiveProp(writable: true)]
    public bool $showingTags = false;

    #[LiveProp(writable: true)]
    public array $tags = [];

    #[ExposeInTemplate(getter: 'getEntity')]
    private ?TaggableInterface $entity = null;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function mount(TaggableInterface $entity, string $tagClass): void
    {
        if (!method_exists($entity, 'getId')) {
            throw new \InvalidArgumentException('Entity must have a getId() method.');
        }

        if (!is_a($tagClass, TagInterface::class, true)) {
            throw new \InvalidArgumentException("Tag class must implement TagInterface. {$tagClass} does not.");
        }

        // Store the real class name (handles Doctrine proxies)
        $reflection = new \ReflectionClass($entity);
        while ($reflection->getParentClass() && str_contains($reflection->getName(), 'Proxies')) {
            $reflection = $reflection->getParentClass();
        }

        $this->entityClass = $reflection->getName();
        $this->entityId = $entity->getId();
        $this->tagClass = $tagClass;
        $this->entity = $entity;
        $this->tags = $entity->getTags()->toArray();
    }

    public function getEntity(): TaggableInterface
    {
        if (!$this->entity) {
            $entity = $this->entityManager->getRepository($this->entityClass)->find($this->entityId);

            if (!$entity) {
                throw new NotFoundHttpException("{$this->entityClass} with id {$this->entityId} not found.");
            }

            if (!$entity instanceof TaggableInterface) {
                throw new \InvalidArgumentException("{$this->entityClass} must implement TaggableInterface.");
            }

            $this->entity = $entity;
        }

        return $this->entity;
    }

    #[LiveAction]
    public function addTag(#[LiveArg] int $tagId): void
    {
        $tag = $this->entityManager->getRepository($this->tagClass)->find($tagId);

        if (!$tag || !$tag instanceof TagInterface) {
            return;
        }

        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
    }

    #[LiveAction]
    public function removeTag(#[LiveArg] int $tagId): void
    {
        $tag = $this->entityManager->getRepository($this->tagClass)->find($tagId);

        if (!$tag) {
            return;
        }

        $key = array_search($tag, $this->tags, true);
        if ($key !== false) {
            unset($this->tags[$key]);
            $this->tags = array_values($this->tags); // Re-index
        }
    }

    #[LiveAction]
    public function toggleTagList(): void
    {
        $this->showingTags = !$this->showingTags;
    }

    #[ExposeInTemplate]
    public function getAllTags(): array
    {
        return $this->entityManager->getRepository($this->tagClass)->findAll();
    }

    #[LiveAction]
    public function saveChanges(): void
    {
        if ($this->readOnly) {
            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Read only mode. Changes not saved.',
                'type' => 'error',
            ]);

            return;
        }

        $entity = $this->getEntity();
        $entity->getTags()->clear();
        foreach ($this->tags as $tag) {
            $entity->addTag($tag);
        }

        try {
            $this->entityManager->flush();

            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Changes saved.',
            ]);

            $this->dispatchBrowserEvent('modal.close', [
                'target' => 'tagManagerModal',
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('toast.show', [
                'message' => 'Error saving changes: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
    }
}
