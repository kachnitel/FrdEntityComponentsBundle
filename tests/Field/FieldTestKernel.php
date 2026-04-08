<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Field;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Minimal kernel for field component functional tests.
 *
 * Uses an in-memory SQLite database so tests are self-contained and fast.
 * Each test run gets a fresh schema via SchemaTool in FieldTestCase::setUp().
 *
 * ## Cache directory
 *
 * Uses a fixed path (not spl_object_hash) so the same directory is reused
 * and rewritten across test runs rather than accumulating in /tmp indefinitely.
 * FieldTestCase::tearDownAfterClass() deletes it after all tests in the class finish.
 *
 * ## Doctrine proxy config compatibility
 *
 * doctrine-bundle 3.x removed proxy/ghost config options entirely.
 * ORM 3 handles proxy generation automatically without them. We detect the
 * bundle version the same way the admin bundle does (checking for a removed
 * interface) and only add the legacy options on doctrine-bundle 2.x.
 *
 * ## Synthetic services
 *
 * KachnitelEntityComponentsBundle registers all components including
 * AttachmentManager (needs FileHandlerInterface + LoggerInterface) and
 * CommentsManager (needs Security). These aren't used in field tests but
 * must be satisfiable for the container to compile. Synthetic services
 * are registered as placeholders and set to mock instances in FieldTestCase.
 */
final class FieldTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new TwigComponentBundle(),
            new LiveComponentBundle(),
            new StimulusBundle(),
            new KachnitelEntityComponentsBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret'               => 'test-secret',
            'test'                 => true,
            'router'               => ['utf8' => true],
            'http_method_override' => false,
            'csrf_protection'      => false,
            'session'              => ['storage_factory_id' => 'session.storage.factory.mock_file'],
            'validation'           => ['enable_attributes' => true],
            'property_info'        => ['enabled' => true],
        ]);

        $container->loadFromExtension('security', [
            'password_hashers' => ['Symfony\Component\Security\Core\User\InMemoryUser' => 'plaintext'],
            'providers'        => ['in_memory' => ['memory' => []]],
            'firewalls'        => ['main' => ['pattern' => '^/', 'security' => false]],
        ]);

        $container->loadFromExtension('twig', [
            'default_path'     => '%kernel.project_dir%/templates',
            'strict_variables' => true,
        ]);

        // Synthetic services required by bundle components not used in field tests.
        // AttachmentManager needs FileHandlerInterface and LoggerInterface;
        // CommentsManager needs Security. Registering them as synthetic keeps the
        // container happy without pulling in real implementations.
        // Actual mock instances are set in FieldTestCase::setUp().
        $container->register('test.file_handler', \Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class)
            ->setSynthetic(true);
        $container->setAlias(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class, 'test.file_handler')
            ->setPublic(true);

        $container->register('test.logger', \Psr\Log\LoggerInterface::class)
            ->setSynthetic(true);
        $container->setAlias(\Psr\Log\LoggerInterface::class, 'test.logger')
            ->setPublic(true);

        $container->register('test.security', \Symfony\Bundle\SecurityBundle\Security::class)
            ->setSynthetic(true);
        $container->setAlias(\Symfony\Bundle\SecurityBundle\Security::class, 'test.security')
            ->setPublic(true);

        $ormConfig = [
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping'    => false,
            'mappings'        => [
                'FieldTests' => [
                    'is_bundle' => false,
                    'type'      => 'attribute',
                    'dir'       => __DIR__ . '/Fixtures',
                    'prefix'    => 'Kachnitel\\EntityComponentsBundle\\Tests\\Field\\Fixtures',
                    'alias'     => 'FieldTests',
                ],
            ],
        ];

        // doctrine-bundle 3.x removed proxy/ghost config options entirely.
        // Detect by checking for an interface that was removed in 3.0.
        /** @disregard P1009 */
        $isDoctrineBundle3 = !interface_exists(
            \Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface::class
        );

        if (!$isDoctrineBundle3) {
            $ormConfig['auto_generate_proxy_classes'] = true;

            if (class_exists(\Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware::class)) {
                $ormConfig['enable_lazy_ghost_objects'] = true;
            }
        }

        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url'    => 'sqlite:///:memory:',
            ],
            'orm' => $ormConfig,
        ]);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/kachnitel_entity_components_bundle/field_tests/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/kachnitel_entity_components_bundle/field_tests/logs';
    }
}
