<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @covers \Kachnitel\EntityComponentsBundle\Field\DefaultEditabilityResolver
 * @group field
 */
class DefaultEditabilityResolverTest extends TestCase
{
    private DefaultEditabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultEditabilityResolver(
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testReturnsTrueForWritableProperty(): void
    {
        $entity = new class {
            public string $name = 'test';
        };

        $this->assertTrue($this->resolver->canEdit($entity, 'name'));
    }

    public function testReturnsTrueWhenSetterExists(): void
    {
        $entity = new class {
            private string $name = '';

            public function setName(string $n): void { $this->name = $n; }

            public function getName(): string { return $this->name; }
        };

        $this->assertTrue($this->resolver->canEdit($entity, 'name'));
    }

    public function testReturnsFalseWhenNoSetter(): void
    {
        $entity = new class {
            private string $readOnly = 'value';

            public function getReadOnly(): string { return $this->readOnly; }
        };

        $this->assertFalse($this->resolver->canEdit($entity, 'readOnly'));
    }

    public function testReturnsFalseForNonExistentProperty(): void
    {
        $entity = new class {};

        $this->assertFalse($this->resolver->canEdit($entity, 'ghost'));
    }
}
