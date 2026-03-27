<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Kachnitel\EntityComponentsBundle\Trait\EntityLiveComponentTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[CoversClass(EntityLiveComponentTrait::class)]
#[Group('trait')]
class EntityLiveComponentTraitTest extends TestCase
{
    private MockEntityLiveComponent $component;

    protected function setUp(): void
    {
        $this->component = new MockEntityLiveComponent();
    }

    // ── mountEntity() ──────────────────────────────────────────────────────────

    public function testMountEntityExtractsClassAndId(): void
    {
        $entity = new MockEntity(42);
        $this->component->mount($entity);

        $this->assertSame(
            MockEntity::class,
            $this->component->getEntityClass()
        );
        $this->assertSame(42, $this->component->getEntityId());
    }

    public function testMountEntityUnwrapsDoctrineProxy(): void
    {
        // Create a mock proxy entity
        $proxyEntity = $this->createMockProxy(99);
        $this->component->mount($proxyEntity);

        // Should unwrap proxy and resolve to real class
        $this->assertSame(
            MockEntity::class,
            $this->component->getEntityClass()
        );
        $this->assertSame(99, $this->component->getEntityId());
    }

    public function testMountEntityThrowsOnMissingGetIdMethod(): void
    {
        $entity = $this->createMock(\stdClass::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must have a getId\(\) method/');

        $this->component->mount($entity);
    }

    public function testMountEntityCastsIdToInt(): void
    {
        $entity = new MockEntity('123');
        $this->component->mount($entity);

        $this->assertSame(123, $this->component->getEntityId());
        $this->assertIsInt($this->component->getEntityId());
    }

    // ── loadEntity() ───────────────────────────────────────────────────────────

    public function testLoadEntityReturnsEntityFromRepository(): void
    {
        $entity = new MockEntity(1);
        $this->component->setEntityClass(MockEntity::class);
        $this->component->setEntityId(1);

        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepository->method('find')
            ->with(1)
            ->willReturn($entity);

        $mockEntityManager->method('getRepository')
            ->with(MockEntity::class)
            ->willReturn($mockRepository);

        $this->component->setEntityManager($mockEntityManager);

        $result = $this->component->load(MockInterface::class);

        $this->assertSame($entity, $result);
    }

    public function testLoadEntityThrowsNotFoundExceptionWhenEntityNotFound(): void
    {
        $this->component->setEntityClass(MockEntity::class);
        $this->component->setEntityId(999);

        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepository->method('find')
            ->with(999)
            ->willReturn(null);

        $mockEntityManager->method('getRepository')
            ->with(MockEntity::class)
            ->willReturn($mockRepository);

        $this->component->setEntityManager($mockEntityManager);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessageMatches('/not found/');

        $this->component->load(MockInterface::class);
    }

    public function testLoadEntityThrowsInvalidArgumentExceptionWhenInterfaceNotImplemented(): void
    {
        $entity = new MockEntity(1);
        $this->component->setEntityClass(MockEntity::class);
        $this->component->setEntityId(1);

        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepository->method('find')
            ->with(1)
            ->willReturn($entity);

        $mockEntityManager->method('getRepository')
            ->with(MockEntity::class)
            ->willReturn($mockRepository);

        $this->component->setEntityManager($mockEntityManager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must implement/');

        // Request an interface the entity doesn't implement
        $this->component->load(\Iterator::class);
    }

    public function testLoadEntityNeverCachesResult(): void
    {
        $entity1 = new MockEntity(1);
        $entity2 = new MockEntity(1);

        $this->component->setEntityClass(MockEntity::class);
        $this->component->setEntityId(1);

        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        // Return different objects on each call
        $mockRepository->method('find')
            ->with(1)
            ->willReturnOnConsecutiveCalls($entity1, $entity2);

        $mockEntityManager->method('getRepository')
            ->with(MockEntity::class)
            ->willReturn($mockRepository);

        $this->component->setEntityManager($mockEntityManager);

        $result1 = $this->component->load(MockInterface::class);
        $result2 = $this->component->load(MockInterface::class);

        // Results should be different objects (not cached)
        $this->assertNotSame($result1, $result2);
    }

    // ── Properties ──────────────────────────────────────────────────────────────

    public function testEntityClassPropertyIsInitiallyEmpty(): void
    {
        $this->assertSame('', $this->component->getEntityClass());
    }

    public function testEntityIdPropertyIsInitiallyZero(): void
    {
        $this->assertSame(0, $this->component->getEntityId());
    }

    // ── Integration ────────────────────────────────────────────────────────────

    public function testMountEntityThenLoadEntityReturnsCorrectEntity(): void
    {
        $entity = new MockEntity(42);

        // First mount
        $this->component->mount($entity);

        // Setup mock EntityManager
        $mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $mockRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $mockRepository->method('find')
            ->with(42)
            ->willReturn($entity);

        $mockEntityManager->method('getRepository')
            ->with(MockEntity::class)
            ->willReturn($mockRepository);

        $this->component->setEntityManager($mockEntityManager);

        // Then load
        $result = $this->component->load(MockInterface::class);

        $this->assertSame($entity, $result);
    }

    /**
     * Create a mock Doctrine proxy for testing proxy unwrapping
     */
    private function createMockProxy(mixed $id): object
    {
        $mockProxy = new class($id) extends MockEntity implements Proxy {
            public function __construct(mixed $id)
            {
                $this->id = $id;
            }

            public function __load(): void {}
            public function __isInitialized(): bool { return true; }
            public function __toString(): string
            {
                return $this->getName();
            }
            public function getName(): string
            {
                return 'Proxies\\__CG__\\' . MockEntity::class;
            }
        };

        return $mockProxy;
    }
}

// Mock implementations for testing

interface MockInterface {}

class MockEntity implements MockInterface
{
    public function __construct(protected mixed $id = null) {}

    public function getId(): mixed
    {
        return $this->id;
    }
}

class MockEntityLiveComponent
{
    use EntityLiveComponentTrait;

    private EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setEntityClass(string $class): void
    {
        $this->entityClass = $class;
    }

    public function setEntityId(int $id): void
    {
        $this->entityId = $id;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function mount(object $entity): void
    {
        $this->mountEntity($entity);
    }

    /**
     * @param class-string $interface
     */
    public function load(string $interface): object
    {
        return $this->loadEntity($interface);
    }
}
