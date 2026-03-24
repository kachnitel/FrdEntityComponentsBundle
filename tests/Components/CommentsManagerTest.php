<?php

declare(strict_types=1);

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

        $this->assertFalse($component->config->readOnly);
        $this->assertSame('comments', $component->config->property);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }

    public function testConfirmIdPropertyExists(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');
        $this->assertNull($component->confirmId);
    }

    public function testReadOnlyOptionCanBeSetViaDto(): void
    {
        $component = $this->factory->get('K:Entity:CommentsManager');
        $component->config = new CommentsManagerOptions(readOnly: true);
        $this->assertTrue($component->config->readOnly);
    }
}
