<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * Base class for component functional tests that require real Doctrine ORM.
 *
 * Boots ComponentFunctionalKernel (in-memory SQLite) and recreates the schema
 * before each test. Provides $this->em and $this->factory.
 *
 * Use this instead of ComponentTestCase when you need to persist fixtures and
 * verify that component actions affect the database.
 */
abstract class ComponentFunctionalTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;
    protected ComponentFactory $factory;

    protected static function getKernelClass(): string
    {
        return ComponentFunctionalKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $container->set('test.file_handler', $this->createMock(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class));
        $container->set('test.logger', $this->createMock(\Psr\Log\LoggerInterface::class));
        $container->set('test.security', $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class));

        $this->em      = $container->get(EntityManagerInterface::class);
        $this->factory = $container->get('ux.twig_component.component_factory');

        $schemaTool = new SchemaTool($this->em);
        $classes    = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public static function tearDownAfterClass(): void
    {
        $cacheDir = sys_get_temp_dir() . '/kachnitel_entity_components_bundle/component_functional_tests/cache';
        if (is_dir($cacheDir)) {
            self::removeDirectory($cacheDir);
        }

        parent::tearDownAfterClass();
    }

    private static function removeDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
