<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle;

use Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler\AttachmentManagerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Registers entity management and inline-edit field components.
 *
 * ## Optional dependencies
 *
 * AttachmentManager requires a FileHandlerInterface implementation. If none is
 * registered, AttachmentManagerPass removes the service at compile time so apps
 * that don't need file handling are not forced to provide one.
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

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // $container->addCompilerPass(new AttachmentManagerPass());
        $container->addCompilerPass(
            new AttachmentManagerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            10  // Higher priority = runs before Symfony's controller locator passes (priority 0)
        );
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
        $container->prependExtensionConfig('twig', [
            'paths' => [
                $this->getPath() . '/templates' => 'KachnitelEntityComponents',
            ],
        ]);

        $container->prependExtensionConfig('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [
                'Kachnitel\\EntityComponentsBundle\\Components\\' => '@KachnitelEntityComponents/components/',
                'Kachnitel\\EntityComponentsBundle\\Components\\Field\\' => '@KachnitelEntityComponents/components/field/',
            ],
        ]);
    }
}
