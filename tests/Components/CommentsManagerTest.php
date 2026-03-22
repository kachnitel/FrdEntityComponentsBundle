<?php

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Kachnitel\EntityComponentsBundle\Components\CommentsManagerOptions;

class CommentsManagerTest extends ComponentTestCase
{
    public function testCommentsManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');
        $this->assertNotNull($component);
    }

    public function testCommentsManagerHasDefaultOptions(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');

        $this->assertFalse($component->options->readOnly);
        $this->assertSame('comments', $component->options->property);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }

    public function testConfirmIdPropertyExists(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');
        $this->assertNull($component->confirmId);
    }

    public function testReadOnlyOptionCanBeSet(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');
        $component->options = new CommentsManagerOptions(readOnly: true);
        $this->assertTrue($component->options->readOnly);
    }
}
