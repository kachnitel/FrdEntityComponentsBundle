<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Registers entity management and inline-edit field components.
 *
 * Follows the same AbstractBundle pattern as KachnitelAdminBundle:
 * loadExtension() imports services.php, prependExtension() declares
 * TwigComponent namespace mappings and Twig paths.
 *
 * The separate KachnitelEntityComponentsExtension class is no longer used —
 * AbstractBundle::loadExtension() takes precedence and the extension class
 * is not invoked when loadExtension() is defined on the bundle itself.
 *
 * @see \Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface
 *      for customising inline-edit permissions
 */
class KachnitelEntityComponentsBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // Register Twig template paths.
        $container->prependExtensionConfig('twig', [
            'paths' => [
                $this->getPath() . '/templates' => 'KachnitelEntityComponents',
            ],
        ]);

        // Map component namespaces to template directories.
        //
        // K:Entity:*        — entity management components (TagManager, AttachmentManager, …)
        // K:Entity:Field:*  — inline-edit field components (StringField, IntField, …)
        $container->prependExtensionConfig('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [
                'Kachnitel\\EntityComponentsBundle\\Components\\' => '@KachnitelEntityComponents/components/',
                'Kachnitel\\EntityComponentsBundle\\Components\\Field\\' => '@KachnitelEntityComponents/components/field/',
            ],
        ]);
    }
}
