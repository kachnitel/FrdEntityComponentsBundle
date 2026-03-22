<?php

namespace Kachnitel\EntityComponentsBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Kachnitel\EntityComponentsBundle\Interface\CommentableInterface;
use Kachnitel\EntityComponentsBundle\Interface\CommentInterface;
use Kachnitel\EntityComponentsBundle\Trait\EntityLiveComponentTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Live Component for managing comments on any CommentableInterface entity.
 *
 * The coupling count (CBO) of this class is inherently high because it integrates
 * Doctrine, Symfony Form, LiveComponent, Security, and HTTP — all of which are
 * required to deliver a self-contained comment-thread UI component. Each dependency
 * serves a distinct, non-removable role in the component's feature set.
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 */
#[AsLiveComponent('K:Entity:CommentsManager', template: '@KachnitelEntityComponents/components/CommentsManager.html.twig')]
final class CommentsManager extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use EntityLiveComponentTrait;

    #[LiveProp]
    public string $commentClass;

    #[LiveProp(hydrateWith: 'hydrateOptions', dehydrateWith: 'dehydrateOptions')]
    public CommentsManagerOptions $options;

    #[LiveProp(writable: true)]
    public ?int $confirmId = null;

    public array $errors = [];

    #[ExposeInTemplate(getter: 'getEntity')]
    private ?CommentableInterface $entity = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
        $this->options = new CommentsManagerOptions();
    }

    public function mount(
        CommentableInterface $entity,
        string $commentClass,
        CommentsManagerOptions $options = new CommentsManagerOptions(),
    ): void {
        if (!is_a($commentClass, CommentInterface::class, true)) {
            throw new \InvalidArgumentException("Comment class must implement CommentInterface. {$commentClass} does not.");
        }

        $this->mountEntity($entity);
        $this->commentClass = $commentClass;
        $this->options      = $options;
        $this->entity       = $entity;
    }

    // ── LiveProp hydration ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    public function hydrateOptions(array $data): CommentsManagerOptions
    {
        return new CommentsManagerOptions(
            readOnly: (bool) ($data['readOnly'] ?? false),
            property: (string) ($data['property'] ?? 'comments'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function dehydrateOptions(CommentsManagerOptions $options): array
    {
        return [
            'readOnly' => $options->readOnly,
            'property' => $options->property,
        ];
    }

    // ── Entity access ─────────────────────────────────────────────────────────

    public function getEntity(): CommentableInterface
    {
        if (!$this->entity) {
            $this->entity = $this->loadEntity(CommentableInterface::class);
        }

        return $this->entity;
    }

    #[ExposeInTemplate('comments')]
    public function getComments(): array
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

        $comments = $entity->$method();

        if (!$comments instanceof \Doctrine\Common\Collections\Collection) {
            throw new Exception(sprintf(
                'Property "%s" is not a Collection on entity "%s"',
                $this->options->property,
                get_class($entity)
            ));
        }

        return $comments->getValues();
    }

    #[ExposeInTemplate]
    public function getMaxTextLength(): int
    {
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
                'attr'  => [
                    'rows'        => 3,
                    'maxlength'   => $this->getMaxTextLength(),
                    'placeholder' => 'Add a comment',
                ],
            ])
            ->setDisabled($this->options->readOnly)
            ->getForm();
    }

    // ── LiveActions ───────────────────────────────────────────────────────────

    #[LiveAction]
    public function submit(Request $request): void
    {
        if ($this->options->readOnly) {
            $this->errors = ['Cannot add comments in read-only mode'];

            return;
        }

        $form = $this->getForm();
        $this->submitForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return;
        }

        try {
            $this->persistComment($form);
            $this->resetForm();
            $this->errors = [];
        } catch (Exception $e) {
            $this->errors = [$e->getMessage()];
        }
    }

    #[LiveAction]
    public function deleteComment(#[LiveArg] int $id, #[LiveArg] string $csrfToken): void
    {
        if ($this->options->readOnly) {
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

    private function persistComment(FormInterface $form): void
    {
        $text = $form->get('text')->getData();
        if ($text === null || trim($text) === '') {
            throw new Exception('Comment cannot be empty');
        }

        $comment = $this->buildComment($text);
        $entity  = $this->getEntity();
        $entity->addComment($comment);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();
    }

    private function buildComment(string $text): CommentInterface
    {
        $commentClass = $this->commentClass;
        $comment      = new $commentClass();

        if (!$comment instanceof CommentInterface) {
            throw new Exception("Failed to create comment instance of type {$commentClass}");
        }

        $comment->setText($text);
        $this->assignCommentAuthor($comment);

        return $comment;
    }

    private function assignCommentAuthor(CommentInterface $comment): void
    {
        $user = $this->security->getUser();
        if ($user !== null && method_exists($comment, 'setCreatedBy')) {
            $comment->setCreatedBy($user);
        }
    }
}
