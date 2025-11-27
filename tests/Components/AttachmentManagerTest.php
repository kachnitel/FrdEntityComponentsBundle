<?php

namespace Frd\EntityComponentsBundle\Tests\Components;

class AttachmentManagerTest extends ComponentTestCase
{
    public function testAttachmentManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('FRD:Entity:AttachmentManager');
        $this->assertNotNull($component);
    }

    public function testAttachmentManagerHasDefaultProperties(): void
    {
        $component = $this->factory->get('FRD:Entity:AttachmentManager');

        $this->assertFalse($component->readOnly);
        $this->assertEquals('attachments', $component->property);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }
}
