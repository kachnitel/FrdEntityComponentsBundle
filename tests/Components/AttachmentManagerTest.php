<?php

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

class AttachmentManagerTest extends ComponentTestCase
{
    public function testAttachmentManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');
        $this->assertNotNull($component);
    }

    public function testAttachmentManagerHasDefaultProperties(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');

        $this->assertFalse($component->readOnly);
        $this->assertEquals('attachments', $component->property);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }
}
