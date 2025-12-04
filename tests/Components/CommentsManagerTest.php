<?php

declare(strict_types=1);

namespace Frd\EntityComponentsBundle\Tests\Components;

use Doctrine\Common\Collections\ArrayCollection;
use Frd\EntityComponentsBundle\Interface\CommentableInterface;
use Frd\EntityComponentsBundle\Interface\CommentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentsManagerTest extends ComponentTestCase
{
    public function testCommentsManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('FRD:Entity:CommentsManager');
        $this->assertNotNull($component);
    }

    public function testCommentsManagerHasDefaultProperties(): void
    {
        $component = $this->factory->get('FRD:Entity:CommentsManager');

        $this->assertFalse($component->readOnly);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }

    public function testConfirmIdPropertyExists(): void
    {
        $component = $this->factory->get('FRD:Entity:CommentsManager');
        $this->assertNull($component->confirmId);
    }

    public function testReadOnlyPropertyCanBeSet(): void
    {
        $component = $this->factory->get('FRD:Entity:CommentsManager');
        $component->readOnly = true;
        $this->assertTrue($component->readOnly);
    }
}
