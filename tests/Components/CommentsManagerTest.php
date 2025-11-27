<?php

namespace Frd\EntityComponentsBundle\Tests\Components;

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
}
