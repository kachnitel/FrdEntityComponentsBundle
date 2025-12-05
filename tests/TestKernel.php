<?php

namespace Kachnitel\EntityComponentsBundle\Tests;

use Kachnitel\EntityComponentsBundle\KachnitelEntityComponentsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new LiveComponentBundle(),
            new StimulusBundle(),
            new KachnitelEntityComponentsBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'secret' => 'test',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'csrf_protection' => true,
            'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
        ]);

        $container->loadFromExtension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
            'strict_variables' => true,
        ]);

        // Register mock services for component dependencies
        $container->register('doctrine.orm.entity_manager', \Doctrine\ORM\EntityManagerInterface::class)
            ->setSynthetic(true);

        $container->setAlias(\Doctrine\ORM\EntityManagerInterface::class, 'doctrine.orm.entity_manager');

        // Mock FileHandlerInterface for AttachmentManager
        $container->register('test.file_handler', \Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class)
            ->setSynthetic(true);

        $container->setAlias(\Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface::class, 'test.file_handler');

        // Mock LoggerInterface
        $container->register('test.logger', \Psr\Log\LoggerInterface::class)
            ->setSynthetic(true);

        $container->setAlias(\Psr\Log\LoggerInterface::class, 'test.logger');

        // Mock Security for CommentsManager
        $container->register('test.security', \Symfony\Bundle\SecurityBundle\Security::class)
            ->setSynthetic(true);

        $container->setAlias(\Symfony\Bundle\SecurityBundle\Security::class, 'test.security');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/frd_entity_components_bundle/cache/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/frd_entity_components_bundle/logs';
    }
}
