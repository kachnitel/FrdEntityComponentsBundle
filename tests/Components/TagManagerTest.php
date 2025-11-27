<?php

namespace Frd\EntityComponentsBundle\Tests\Components;

class TagManagerTest extends ComponentTestCase
{
    public function testTagManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('FRD:Entity:TagManager');
        $this->assertNotNull($component);
    }

    public function testTagManagerHasDefaultProperties(): void
    {
        $component = $this->factory->get('FRD:Entity:TagManager');

        $this->assertFalse($component->readOnly);
        $this->assertFalse($component->showingTags);
        $this->assertIsArray($component->tags);
        $this->assertEmpty($component->tags);
    }
}
