<?php

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Kachnitel\EntityComponentsBundle\Components\AttachmentManagerOptions;

class AttachmentManagerTest extends ComponentTestCase
{
    public function testAttachmentManagerComponentCanBeCreated(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');
        $this->assertNotNull($component);
    }

    public function testAttachmentManagerHasDefaultOptions(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');

        $this->assertFalse($component->options->readOnly);
        $this->assertSame('attachments', $component->options->property);
        $this->assertNull($component->options->tagClass);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }

    public function testAttachmentManagerOptionsCanBeCustomised(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');
        $component->options = new AttachmentManagerOptions(
            readOnly: true,
            property: 'files',
            tagClass: 'App\\Entity\\Tag',
        );

        $this->assertTrue($component->options->readOnly);
        $this->assertSame('files', $component->options->property);
        $this->assertSame('App\\Entity\\Tag', $component->options->tagClass);
    }
}
