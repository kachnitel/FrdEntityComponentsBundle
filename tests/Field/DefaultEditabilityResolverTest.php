<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[CoversClass(DefaultEditabilityResolver::class)]
#[Group('field')]
class DefaultEditabilityResolverTest extends TestCase
{
    private DefaultEditabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DefaultEditabilityResolver(
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testReturnsTrueForPublicProperty(): void
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

    public function testReturnsTrueForNullableProperty(): void
    {
        $entity = new class {
            private ?string $description = null;

            public function setDescription(?string $d): void { $this->description = $d; }

            public function getDescription(): ?string { return $this->description; }
        };

        $this->assertTrue($this->resolver->canEdit($entity, 'description'));
    }

    public function testReturnsFalseForGetterOnlyProperty(): void
    {
        $entity = new class {
            public function getComputedValue(): string { return 'computed'; }
        };

        $this->assertFalse($this->resolver->canEdit($entity, 'computedValue'));
    }
}
