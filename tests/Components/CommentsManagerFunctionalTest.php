<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Kachnitel\EntityComponentsBundle\Components\CommentsManager;
use Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures\ComponentTestComment;
use Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures\ComponentTestCommentableEntity;
use Kachnitel\EntityComponentsBundle\Tests\Components\Fixtures\ComponentTestTag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional tests for CommentsManager using real SQLite ORM.
 *
 * Covers mount(), getEntity(), getComments(), and getMaxTextLength().
 * submit() and deleteComment() are omitted: they depend on Request injection,
 * CSRF validation, and dispatchBrowserEvent() which require a full HTTP context.
 */
#[CoversClass(CommentsManager::class)]
#[Group('component')]
#[Group('component-comments-manager')]
class CommentsManagerFunctionalTest extends ComponentFunctionalTestCase
{
    private function createEntity(string $title = 'Article'): ComponentTestCommentableEntity
    {
        $entity = new ComponentTestCommentableEntity($title);
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function createEntityWithComments(int $count = 2): ComponentTestCommentableEntity
    {
        $entity = new ComponentTestCommentableEntity('With Comments');

        for ($i = 1; $i <= $count; $i++) {
            $comment = (new ComponentTestComment())->setText("Comment $i");
            $this->em->persist($comment);
            $entity->addComment($comment);
        }

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function getComponent(): CommentsManager
    {
        /** @var CommentsManager */
        return $this->factory->get('K:Entity:CommentsManager');
    }

    // ── mount() ───────────────────────────────────────────────────────────────

    public function testMountSetsEntityClassAndId(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $this->assertSame(ComponentTestCommentableEntity::class, $component->entityClass);
        $this->assertSame($entity->getId(), $component->entityId);
    }

    public function testMountSetsCommentClass(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $this->assertSame(ComponentTestComment::class, $component->commentClass);
    }

    public function testMountThrowsForEntityWithoutGetId(): void
    {
        $badEntity = new class implements \Kachnitel\EntityComponentsBundle\Interface\CommentableInterface {
            public function getComments(): \Doctrine\Common\Collections\Collection
            {
                return new \Doctrine\Common\Collections\ArrayCollection();
            }

            public function addComment(\Kachnitel\EntityComponentsBundle\Interface\CommentInterface $c): static { return $this; }

            public function removeComment(\Kachnitel\EntityComponentsBundle\Interface\CommentInterface $c): static { return $this; }
        };

        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('getId()');

        $component->mount($badEntity, ComponentTestComment::class);
    }

    public function testMountThrowsForClassNotImplementingCommentInterface(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CommentInterface');

        $component->mount($entity, \stdClass::class);
    }

    // ── getEntity() ───────────────────────────────────────────────────────────

    public function testGetEntityReturnsCorrectEntity(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $this->assertSame($entity, $component->getEntity());
    }

    public function testGetEntityFetchesFromDatabaseAfterCacheCleared(): void
    {
        $entity    = $this->createEntity('Fetched');
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        // Simulate post-hydrate state: clear the cached entity reference
        $ref = new \ReflectionProperty($component, 'entity');
        $ref->setAccessible(true);
        $ref->setValue($component, null);

        /** @var ComponentTestCommentableEntity $fetched */
        $fetched = $component->getEntity();
        $this->assertSame($entity->getId(), $fetched->getId());
    }

    public function testGetEntityThrowsWhenEntityNotFound(): void
    {
        $component               = $this->getComponent();
        $component->entityClass  = ComponentTestCommentableEntity::class;
        $component->entityId     = 99999;
        $component->commentClass = ComponentTestComment::class;

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $component->getEntity();
    }

    // ── getComments() ─────────────────────────────────────────────────────────

    public function testGetCommentsReturnsAllComments(): void
    {
        $entity    = $this->createEntityWithComments(3);
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $comments = $component->getComments();

        $this->assertCount(3, $comments);
    }

    public function testGetCommentsReturnsEmptyArrayWhenNoComments(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $this->assertSame([], $component->getComments());
    }

    public function testGetCommentsReturnsPlainArray(): void
    {
        $entity    = $this->createEntityWithComments(2);
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $this->assertIsArray($component->getComments());
    }

    public function testGetCommentsContainsCommentObjects(): void
    {
        $entity    = $this->createEntityWithComments(1);
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        $comments = $component->getComments();
        $this->assertInstanceOf(ComponentTestComment::class, $comments[0]);
        $this->assertSame('Comment 1', $comments[0]->getText());
    }

    // ── getMaxTextLength() ────────────────────────────────────────────────────

    public function testGetMaxTextLengthUsesConstantFromCommentClass(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        // ComponentTestComment::MAX_TEXT_LENGTH = 500
        $this->assertSame(500, $component->getMaxTextLength());
    }

    public function testGetMaxTextLengthFallsBackToDefaultWhenNoConstant(): void
    {
        $entity    = $this->createEntity();
        $component = $this->getComponent();
        $component->mount($entity, ComponentTestComment::class);

        // Override with a class that has no MAX_TEXT_LENGTH constant
        $component->commentClass = ComponentTestTag::class;

        $this->assertSame(4096, $component->getMaxTextLength());
    }

    // ── Default properties ────────────────────────────────────────────────────

    public function testDefaultPropertiesAreCorrect(): void
    {
        $component = $this->getComponent();

        $this->assertFalse($component->readOnly);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
        $this->assertNull($component->confirmId);
    }
}
