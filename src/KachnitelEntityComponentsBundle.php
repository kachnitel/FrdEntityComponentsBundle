<?php

namespace Kachnitel\EntityComponentsBundle;

use Kachnitel\EntityComponentsBundle\Components\AttachmentManager;
use Kachnitel\EntityComponentsBundle\Components\CommentsManager;
use Kachnitel\EntityComponentsBundle\Components\SelectRelationship;
use Kachnitel\EntityComponentsBundle\Components\TagManager;
use Kachnitel\EntityComponentsBundle\Twig\ColorConverterExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class KachnitelEntityComponentsBundle extends AbstractBundle
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

        $container->register(SelectRelationship::class)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        // Register Twig extension
        $container->register(ColorConverterExtension::class)
            ->setAutowired(true)
            ->addTag('twig.extension');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // Register Twig paths
        $container->prependExtensionConfig('twig', [
            'paths' => [
                $this->getPath() . '/templates' => 'KachnitelEntityComponents',
            ],
        ]);
    }
}
