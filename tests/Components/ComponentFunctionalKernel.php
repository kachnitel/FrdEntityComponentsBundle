<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\Tests\Components;

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
 * Minimal kernel for component functional tests that need real Doctrine ORM.
 *
 * Mirrors the setup in FieldTestKernel but maps tests/Components/Fixtures/
 * for the ORM entity scan.
 */
final class ComponentFunctionalKernel extends Kernel
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
                'ComponentTests' => [
                    'is_bundle' => false,
                    'type'      => 'attribute',
                    'dir'       => __DIR__ . '/Fixtures',
                    'prefix'    => 'Kachnitel\\EntityComponentsBundle\\Tests\\Components\\Fixtures',
                    'alias'     => 'ComponentTests',
                ],
            ],
        ];

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
        return sys_get_temp_dir() . '/kachnitel_entity_components_bundle/component_functional_tests/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/kachnitel_entity_components_bundle/component_functional_tests/logs';
    }
}
