<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class KachnitelEntityComponentsExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Map component namespaces to template directories.
        //
        // K:Entity:*        — entity management components (TagManager, AttachmentManager, …)
        // K:Entity:Field:*  — inline-edit field components (StringField, IntField, …)
        //
        // TwigComponent uses the last segment after the final ":" as the template name,
        // so K:Entity:Field:String → @KachnitelEntityComponents/components/field/StringField.html.twig
        $container->prependExtensionConfig('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [
                'Kachnitel\\EntityComponentsBundle\\Components\\' => '@KachnitelEntityComponents/components/',
                'Kachnitel\\EntityComponentsBundle\\Field\\'      => '@KachnitelEntityComponents/components/field/',
            ],
        ]);
    }
}
