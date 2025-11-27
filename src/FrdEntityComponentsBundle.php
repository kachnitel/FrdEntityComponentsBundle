<?php

namespace Frd\EntityComponentsBundle;

use Frd\EntityComponentsBundle\Components\AttachmentManager;
use Frd\EntityComponentsBundle\Components\CommentsManager;
use Frd\EntityComponentsBundle\Components\TagManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class FrdEntityComponentsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register component classes
        $container->register(TagManager::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        $container->register(AttachmentManager::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        $container->register(CommentsManager::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // Register Twig paths
        $container->prependExtensionConfig('twig', [
            'paths' => [
                $this->getPath() . '/templates' => 'FrdEntityComponents',
            ],
        ]);
    }
}
