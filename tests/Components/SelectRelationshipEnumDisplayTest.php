<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Doctrine\ORM\EntityManagerInterface;
use Kachnitel\EntityComponentsBundle\Components\SelectRelationship;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Covers the SelectRelationship::getCurrentDisplayValue() enum path, which is
 * the only method not covered by the existing SelectRelationshipTest.
 *
 * Uses only the Symfony 7.1+ getType() API (ObjectType) — the Symfony 6.4
 * getTypes() fallback path is already covered in SelectRelationshipTest.
 */
#[CoversClass(SelectRelationship::class)]
#[Group('component')]
#[Group('component-select-relationship')]
class SelectRelationshipEnumDisplayTest extends TestCase
{
    /**
     * @param PropertyInfoExtractorInterface&MockObject $mock
     * @param class-string                              $className
     */
    private function mockPropertyInfoForClass(MockObject $mock, string $className): void
    {
        $mock->method('getType')->willReturn(new ObjectType($className));
    }

    public function testGetCurrentDisplayValueReturnsEnumCaseName(): void
    {
        $entity = new class {
            public function getId(): int { return 1; }
        };

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function (object $object, string $property): mixed {
                if ($property === 'id') {
                    return 1;
                }
                if ($property === 'status') {
                    return SREnumDisplayTestStatus::Active;
                }

                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $this->mockPropertyInfoForClass($propertyInfo, SREnumDisplayTestStatus::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');

        // BackedEnum without displayValue() falls back to ->name
        $this->assertSame('Active', $component->getCurrentDisplayValue());
    }

    public function testGetCurrentDisplayValueReturnsPlaceholderForNullEnum(): void
    {
        $entity = new class {
            public function getId(): int { return 1; }
        };

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function (object $object, string $property): mixed {
                return $property === 'id' ? 1 : null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $this->mockPropertyInfoForClass($propertyInfo, SREnumDisplayTestStatus::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component              = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->placeholder = 'Pick one';
        $component->mount($entity, 'status');

        $this->assertSame('Pick one', $component->getCurrentDisplayValue());
    }

    public function testGetCurrentDisplayValueUsesDisplayValueMethodWhenAvailable(): void
    {
        $entity = new class {
            public function getId(): int { return 1; }
        };

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function (object $object, string $property): mixed {
                if ($property === 'id') {
                    return 1;
                }
                if ($property === 'status') {
                    return SREnumDisplayTestStatusWithDisplay::Active;
                }

                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $this->mockPropertyInfoForClass($propertyInfo, SREnumDisplayTestStatusWithDisplay::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');

        $this->assertSame('Is Active', $component->getCurrentDisplayValue());
    }
}

// ── Local test fixtures ───────────────────────────────────────────────────────

enum SREnumDisplayTestStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}

enum SREnumDisplayTestStatusWithDisplay: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function displayValue(): string
    {
        return match ($this) {
            self::Active   => 'Is Active',
            self::Inactive => 'Is Inactive',
        };
    }
}
