<?php

namespace Frd\EntityComponentsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Frd\EntityComponentsBundle\Interface\CommentableInterface;
use Frd\EntityComponentsBundle\Interface\CommentInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('FRD:Entity:CommentsManager', template: '@FrdEntityComponents/components/CommentsManager.html.twig')]
final class CommentsManager extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public int $entityId;

    #[LiveProp]
    public string $entityClass;

    #[LiveProp]
    public string $commentClass;

    #[LiveProp]
    public string $property = 'comments';

    #[LiveProp]
    public bool $readOnly = false;

    #[LiveProp(writable: true)]
    public ?int $confirmId = null;

    public array $errors = [];

    #[ExposeInTemplate(getter: 'getEntity')]
    private ?CommentableInterface $entity = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function mount(CommentableInterface $entity, string $commentClass): void
    {
        if (!method_exists($entity, 'getId')) {
            throw new \InvalidArgumentException('Entity must have a getId() method.');
        }

        if (!is_a($commentClass, CommentInterface::class, true)) {
            throw new \InvalidArgumentException("Comment class must implement CommentInterface. {$commentClass} does not.");
        }

        // Store the real class name (handles Doctrine proxies)
        $reflection = new \ReflectionClass($entity);
        while ($reflection->getParentClass() && str_contains($reflection->getName(), 'Proxies')) {
            $reflection = $reflection->getParentClass();
        }

        $this->entityClass = $reflection->getName();
        $this->entityId = $entity->getId();
        $this->commentClass = $commentClass;
        $this->entity = $entity;
    }

    public function getEntity(): CommentableInterface
    {
        if (!$this->entity) {
            $entity = $this->entityManager->getRepository($this->entityClass)->find($this->entityId);

            if (!$entity) {
                throw new NotFoundHttpException("{$this->entityClass} with id {$this->entityId} not found.");
            }

            if (!$entity instanceof CommentableInterface) {
                throw new \InvalidArgumentException("{$this->entityClass} must implement CommentableInterface.");
            }

            $this->entity = $entity;
        }

        return $this->entity;
    }

    #[ExposeInTemplate('comments')]
    public function getComments(): array
    {
        $entity = $this->getEntity();
        $method = 'get' . ucfirst($this->property);

        if (!method_exists($entity, $method)) {
            throw new Exception(sprintf(
                'Method "%s" does not exist on entity "%s"',
                $method,
                get_class($entity)
            ));
        }

        $comments = $entity->$method();

        if (!$comments instanceof \Doctrine\Common\Collections\Collection) {
            throw new Exception(sprintf(
                'Property "%s" is not a Collection on entity "%s"',
                $this->property,
                get_class($entity)
            ));
        }

        return $comments->getValues();
    }

    #[ExposeInTemplate]
    public function getMaxTextLength(): int
    {
        // Default max length, can be overridden by the comment class if it has a constant
        if (defined("{$this->commentClass}::MAX_TEXT_LENGTH")) {
            return constant("{$this->commentClass}::MAX_TEXT_LENGTH");
        }
        return 4096;
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->add('text', TextareaType::class, [
                'label' => 'Add a comment',
                'attr' => [
                    'rows' => 3,
                    'maxlength' => $this->getMaxTextLength(),
                    'placeholder' => 'Add a comment'
                ]
            ])
            ->setDisabled($this->readOnly)
            ->getForm();
    }

    #[LiveAction]
    public function submit(Request $request): void
    {
        if ($this->readOnly) {
            $this->errors = ['Cannot add comments in read-only mode'];
            return;
        }

        $form = $this->getForm();
        $this->submitForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $text = $form->get('text')->getData();
                if (is_null($text) || trim($text) === '') {
                    $this->errors = ['Comment cannot be empty'];
                    return;
                }

                // Create new comment instance using reflection
                $commentClass = $this->commentClass;
                $comment = new $commentClass();

                if (!$comment instanceof CommentInterface) {
                    throw new Exception("Failed to create comment instance of type {$commentClass}");
                }

                $comment->setText($text);

                // Set created by if the comment has a setCreatedBy method
                $user = $this->security->getUser();
                if ($user && method_exists($comment, 'setCreatedBy')) {
                    $comment->setCreatedBy($user);
                }

                $entity = $this->getEntity();
                $entity->addComment($comment);

                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $this->resetForm();
                $this->errors = [];
            } catch (Exception $e) {
                $this->errors = [$e->getMessage()];
            }
        }
    }

    #[LiveAction]
    public function deleteComment(#[LiveArg] int $id, #[LiveArg] string $csrfToken): void
    {
        if ($this->readOnly) {
            throw new Exception('Cannot delete comment in read-only mode');
        }

        if (!$this->isCsrfTokenValid('delete-comment-' . $id, $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        if ($this->confirmId !== $id) {
            $this->confirmId = $id;
            return;
        }
        $this->confirmId = null;

        try {
            /** @var CommentInterface $comment */
            $comment = $this->entityManager
                ->getRepository($this->commentClass)
                ->find($id);

            if (!$comment) {
                throw new Exception('Comment not found');
            }

            $user = $this->security->getUser();

            // Check if user created this comment
            if (method_exists($comment, 'getCreatedBy')) {
                if ($comment->getCreatedBy() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                    throw new Exception('Cannot delete comment created by another user');
                }
            }

            $entity = $this->getEntity();
            $entity->removeComment($comment);
            $this->entityManager->remove($comment);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errors = [$e->getMessage()];
        }
    }
}
