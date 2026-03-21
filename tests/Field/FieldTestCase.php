<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for inline-edit field component functional tests.
 *
 * Boots FieldTestKernel (in-memory SQLite) and recreates the schema before
 * each test. Use $this->em to persist fixtures; retrieve field components
 * from the container via getFieldComponent().
 *
 * ## Why functional (container) tests instead of unit tests
 *
 * Field components use PropertyInfoTrait which declares $doctrineExtractor as a
 * typed non-nullable property initialized only by a #[Required] method. Constructing
 * the component manually bypasses the Symfony DI lifecycle, leaving $doctrineExtractor
 * uninitialized. Getting components from the real container ensures full DI lifecycle
 * including #[Required] method injection.
 *
 * ## Synthetic services
 *
 * FieldTestKernel registers FileHandlerInterface, LoggerInterface, and Security as
 * synthetic placeholders (required by AttachmentManager and CommentsManager which are
 * part of the bundle but not used in field tests). setUp() sets mock instances so the
 * container can resolve them if any code path touches those services.
 *
 * ## Cache cleanup
 *
 * FieldTestKernel uses a fixed cache directory. tearDownAfterClass() removes it so
 * test runs do not accumulate stale cache directories in /tmp.
 */
abstract class FieldTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected static function getKernelClass(): string
    {
        return FieldTestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        // Satisfy synthetic service placeholders declared in FieldTestKernel.
        $container->set('test.file_handler', $this->createMock(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class));
        $container->set('test.logger', $this->createMock(\Psr\Log\LoggerInterface::class));
        $container->set('test.security', $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class));

        $this->em = $container->get(EntityManagerInterface::class);

        // Recreate schema from scratch so each test is isolated.
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
        // Remove the fixed kernel cache directory created by FieldTestKernel so
        // test runs do not accumulate stale entries in /tmp.
        $cacheDir = sys_get_temp_dir() . '/kachnitel_entity_components_bundle/field_tests/cache';
        if (is_dir($cacheDir)) {
            self::removeDirectory($cacheDir);
        }

        parent::tearDownAfterClass();
    }

    /**
     * Retrieve a field component from the container, ensuring the full DI lifecycle
     * including all #[Required] setters.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    protected function getFieldComponent(string $class): object
    {
        /** @var T */
        return static::getContainer()->get($class);
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
