<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Kachnitel\EntityComponentsBundle\Components\SelectRelationship;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class SelectRelationshipTest extends ComponentTestCase
{
    public function testSelectRelationshipComponentCanBeCreated(): void
    {
        $component = $this->factory->get('K:Entity:SelectRelationship');
        $this->assertNotNull($component);
    }

    public function testSelectRelationshipHasDefaultProperties(): void
    {
        $component = $this->factory->get('K:Entity:SelectRelationship');

        $this->assertSame('-', $component->placeholder);
        $this->assertSame('id', $component->valueProperty);
        $this->assertSame('name', $component->displayProperty);
        $this->assertFalse($component->disableEmpty);
        $this->assertFalse($component->disabled);
        $this->assertNull($component->role);
        $this->assertNull($component->viewRole);
        $this->assertNull($component->label);
        $this->assertNull($component->repositoryMethod);
        $this->assertIsArray($component->filter);
        $this->assertEmpty($component->filter);
        $this->assertIsArray($component->repositoryArgs);
        $this->assertEmpty($component->repositoryArgs);
    }

    public function testSelectRelationshipValueProperty(): void
    {
        $component = $this->factory->get('K:Entity:SelectRelationship');

        $this->assertNull($component->value);
        $this->assertSame('', $component->entityClass);
        $this->assertNull($component->entityId);
        $this->assertSame('', $component->property);
    }

    public function testMountInitializesComponentWithEntity(): void
    {
        $entity = new TestEntity(42);
        $relatedEntity = new TestRelatedEntity(5, 'Test Related');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) use ($relatedEntity) {
                if ($property === 'id') {
                    return $object->getId();
                }
                if ($property === 'related' && $object instanceof TestEntity) {
                    return $relatedEntity;
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertSame(TestEntity::class, $component->entityClass);
        $this->assertSame('42', $component->entityId);
        $this->assertSame('related', $component->property);
        $this->assertSame('5', $component->value);
    }

    public function testMountInitializesNullValueWhenPropertyIsNull(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertNull($component->value);
    }

    public function testMountInitializesWithEnumValue(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                if ($property === 'status') {
                    return TestStatus::Active;
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestStatus::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');

        $this->assertSame('active', $component->value);
    }

    public function testGetOptionsReturnsEnumCases(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return TestStatus::Active;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestStatus::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');

        $options = $component->getOptions();

        $this->assertCount(3, $options);
        $this->assertSame(TestStatus::cases(), $options);
    }

    public function testGetOptionsReturnsEntitiesFromRepository(): void
    {
        $entity = new TestEntity(42);
        $related1 = new TestRelatedEntity(1, 'First');
        $related2 = new TestRelatedEntity(2, 'Second');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityRepository<TestRelatedEntity>&MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([$related1, $related2]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $options = $component->getOptions();

        $this->assertCount(2, $options);
        $this->assertSame($related1, $options[0]);
        $this->assertSame($related2, $options[1]);
    }

    public function testGetOptionsUsesFilterWhenProvided(): void
    {
        $entity = new TestEntity(42);
        $related1 = new TestRelatedEntity(1, 'Active One');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityRepository<TestRelatedEntity>&MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$related1]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');
        $component->filter = ['active' => true];

        $options = $component->getOptions();

        $this->assertCount(1, $options);
    }

    public function testGetOptionsUsesCustomRepositoryMethod(): void
    {
        $entity = new TestEntity(42);
        $related1 = new TestRelatedEntity(1, 'Manager');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityRepository<TestRelatedEntity>&MockObject $repository */
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findByRoles'])
            ->getMock();
        $repository->method('findByRoles')
            ->with(['ROLE_MANAGER'])
            ->willReturn([$related1]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');
        $component->repositoryMethod = 'findByRoles';
        $component->repositoryArgs = [['ROLE_MANAGER']];

        $options = $component->getOptions();

        $this->assertCount(1, $options);
    }

    public function testGetOptionValueReturnsEnumValue(): void
    {
        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);

        $this->assertSame('active', $component->getOptionValue(TestStatus::Active));
        $this->assertSame('inactive', $component->getOptionValue(TestStatus::Inactive));
    }

    public function testGetOptionValueReturnsEntityPropertyValue(): void
    {
        $entity = new TestRelatedEntity(123, 'Test');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->with($entity, 'id')
            ->willReturn(123);

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);

        $this->assertSame('123', $component->getOptionValue($entity));
    }

    public function testGetOptionLabelReturnsEnumName(): void
    {
        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);

        $this->assertSame('Active', $component->getOptionLabel(TestStatus::Active));
        $this->assertSame('Inactive', $component->getOptionLabel(TestStatus::Inactive));
    }

    public function testGetOptionLabelReturnsEnumDisplayValueWhenAvailable(): void
    {
        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);

        $this->assertSame('Is Active', $component->getOptionLabel(TestStatusWithDisplay::Active));
        $this->assertSame('Is Inactive', $component->getOptionLabel(TestStatusWithDisplay::Inactive));
    }

    public function testGetOptionLabelReturnsEntityPropertyValue(): void
    {
        $entity = new TestRelatedEntity(123, 'Test Name');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->with($entity, 'name')
            ->willReturn('Test Name');

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);

        $this->assertSame('Test Name', $component->getOptionLabel($entity));
    }

    public function testIsSelectedReturnsTrueForMatchingValue(): void
    {
        $entity = new TestEntity(42);
        $relatedEntity = new TestRelatedEntity(5, 'Test');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) use ($relatedEntity) {
                if ($property === 'id') {
                    return $object->getId();
                }
                if ($property === 'related') {
                    return $relatedEntity;
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertTrue($component->isSelected($relatedEntity));
    }

    public function testIsSelectedReturnsFalseForNonMatchingValue(): void
    {
        $entity = new TestEntity(42);
        $relatedEntity = new TestRelatedEntity(5, 'Test');
        $otherEntity = new TestRelatedEntity(10, 'Other');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) use ($relatedEntity) {
                if ($property === 'id') {
                    return $object->getId();
                }
                if ($property === 'related') {
                    return $relatedEntity;
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertFalse($component->isSelected($otherEntity));
    }

    public function testIsEnumTypeReturnsTrueForEnum(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return TestStatus::Active;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestStatus::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');

        $this->assertTrue($component->isEnumType());
    }

    public function testIsEnumTypeReturnsFalseForEntity(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertFalse($component->isEnumType());
    }

    public function testGetCurrentDisplayValueReturnsPlaceholderWhenNull(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->placeholder = 'Select...';
        $component->mount($entity, 'related');

        $this->assertSame('Select...', $component->getCurrentDisplayValue());
    }

    public function testGetCurrentDisplayValueReturnsEntityLabel(): void
    {
        $entity = new TestEntity(42);
        $relatedEntity = new TestRelatedEntity(5, 'Related Name');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) use ($relatedEntity) {
                if ($property === 'id') {
                    return $object->getId();
                }
                if ($property === 'related' && $object instanceof TestEntity) {
                    return $relatedEntity;
                }
                if ($property === 'name') {
                    return 'Related Name';
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertSame('Related Name', $component->getCurrentDisplayValue());
    }

    public function testOnValueChangedUpdatesEntityWithRelatedEntity(): void
    {
        $entity = new TestEntityWithSetter(42);
        $newRelated = new TestRelatedEntity(10, 'New Related');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('find')
            ->with(TestRelatedEntity::class, '10')
            ->willReturn($newRelated);
        $em->expects($this->once())->method('persist')->with($entity);
        $em->expects($this->once())->method('flush');

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');
        $component->value = '10';
        $component->onValueChanged();

        $this->assertSame($newRelated, $entity->getRelated());
    }

    public function testOnValueChangedSetsNullWhenValueEmpty(): void
    {
        $entity = new TestEntityWithSetter(42);
        $entity->setRelated(new TestRelatedEntity(5, 'Old'));

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($entity);
        $em->expects($this->once())->method('flush');

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');
        $component->value = '';
        $component->onValueChanged();

        $this->assertNull($entity->getRelated());
    }

    public function testOnValueChangedUpdatesEntityWithEnum(): void
    {
        $entity = new TestEntityWithEnumSetter(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestStatus::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($entity);
        $em->expects($this->once())->method('flush');

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'status');
        $component->value = 'inactive';
        $component->onValueChanged();

        $this->assertSame(TestStatus::Inactive, $entity->getStatus());
    }

    public function testGetEntityThrowsExceptionWhenNotFound(): void
    {
        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn(null);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->entityClass = TestEntity::class;
        $component->entityId = '999';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Entity ' . TestEntity::class . ' with ID 999 not found');

        $component->getEntity();
    }

    public function testGetOptionsReturnsEmptyArrayWhenTargetClassNotResolved(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')->willReturn(null);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'nonExistentProperty');

        $this->assertSame([], $component->getOptions());
    }

    public function testGetEntityObjectReturnsEntity(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        $this->assertSame($entity, $component->getEntityObject());
    }

    public function testResolveFromReflectionWhenPropertyInfoReturnsNull(): void
    {
        $entity = new TestEntityWithTypedProperty(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')->willReturn(null);

        /** @var EntityRepository<TestRelatedEntity>&MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findAll')->willReturn([]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');

        // Should resolve from reflection and detect it's not an enum
        $this->assertFalse($component->isEnumType());
        $this->assertSame([], $component->getOptions());
    }

    public function testOnValueChangedDoesNothingWhenSetterMissing(): void
    {
        $entity = new TestEntity(42);

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'id') {
                    return $object->getId();
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);
        $propertyInfo->method('getTypes')
            ->willReturn([new Type(Type::BUILTIN_TYPE_OBJECT, false, TestRelatedEntity::class)]);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->mount($entity, 'related');
        $component->value = '10';
        $component->onValueChanged();

        // No exception, just returns early
        $this->assertTrue(true);
    }

    public function testCustomValueAndDisplayProperties(): void
    {
        $entity = new TestRelatedEntity(123, 'Custom Name');

        /** @var PropertyAccessorInterface&MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')
            ->willReturnCallback(function ($object, $property) {
                if ($property === 'code') {
                    return 'ABC123';
                }
                if ($property === 'title') {
                    return 'Custom Title';
                }
                return null;
            });

        /** @var PropertyInfoExtractorInterface&MockObject $propertyInfo */
        $propertyInfo = $this->createMock(PropertyInfoExtractorInterface::class);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $component = new SelectRelationship($em, $propertyInfo, $propertyAccessor);
        $component->valueProperty = 'code';
        $component->displayProperty = 'title';

        $this->assertSame('ABC123', $component->getOptionValue($entity));
        $this->assertSame('Custom Title', $component->getOptionLabel($entity));
    }
}

// Test fixtures

class TestEntity
{
    public function __construct(private int $id) {}

    public function getId(): int
    {
        return $this->id;
    }
}

class TestEntityWithSetter extends TestEntity
{
    private ?TestRelatedEntity $related = null;

    public function setRelated(?TestRelatedEntity $related): void
    {
        $this->related = $related;
    }

    public function getRelated(): ?TestRelatedEntity
    {
        return $this->related;
    }
}

class TestEntityWithEnumSetter extends TestEntity
{
    private ?TestStatus $status = null;

    public function setStatus(?TestStatus $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?TestStatus
    {
        return $this->status;
    }
}

class TestEntityWithTypedProperty extends TestEntity
{
    private ?TestRelatedEntity $related = null;

    public function setRelated(?TestRelatedEntity $related): void
    {
        $this->related = $related;
    }

    public function getRelated(): ?TestRelatedEntity
    {
        return $this->related;
    }
}

class TestRelatedEntity
{
    public function __construct(
        private int $id,
        private string $name,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

enum TestStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}

enum TestStatusWithDisplay: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function displayValue(): string
    {
        return match ($this) {
            self::Active => 'Is Active',
            self::Inactive => 'Is Inactive',
        };
    }
}
