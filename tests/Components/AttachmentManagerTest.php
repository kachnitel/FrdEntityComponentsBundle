<?php

declare(strict_types=1);

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

        $this->assertFalse($component->config->readOnly);
        $this->assertSame('attachments', $component->config->property);
        $this->assertNull($component->config->tagClass);
        $this->assertIsArray($component->errors);
        $this->assertEmpty($component->errors);
    }

    public function testAttachmentManagerOptionsCanBeCustomisedViaDto(): void
    {
        $component = $this->factory->get('K:Entity:AttachmentManager');
        $component->config = new AttachmentManagerOptions(
            readOnly: true,
            property: 'files',
            tagClass: 'App\\Entity\\Tag',
        );

        $this->assertTrue($component->config->readOnly);
        $this->assertSame('files', $component->config->property);
        $this->assertSame('App\\Entity\\Tag', $component->config->tagClass);
    }
}
