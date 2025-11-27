<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Frd\EntityComponentsBundle\Twig\ColorConverterExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    // Register all components from the Components directory
    $services->load('Frd\\EntityComponentsBundle\\Components\\', dirname(__DIR__) . '/src/Components');

    // Register Twig extensions explicitly
    $services->set(ColorConverterExtension::class)
        ->tag('twig.extension');
};
