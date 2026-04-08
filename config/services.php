<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Kachnitel\EntityComponentsBundle\Components\Field\DefaultEditabilityResolver;
use Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface;
use Kachnitel\EntityComponentsBundle\Twig\ColorConverterExtension;
use Kachnitel\EntityComponentsBundle\Twig\UtilExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    // ── Entity management components ───────────────────────────────────────────
    // Exclude the Field subdirectory — it is registered separately below
    // with share(false) because field components hold per-entity LiveProp state.
    $services->load(
        'Kachnitel\\EntityComponentsBundle\\Components\\',
        dirname(__DIR__) . '/src/Components'
    )->exclude([
        dirname(__DIR__) . '/src/Components/Field/',
    ]);

    // ── Inline-edit field components ───────────────────────────────────────────
    // Non-shared: each call to container->get() returns a fresh instance.
    // This is required because field components hold per-entity LiveProp state
    // ($entityClass, $entityId, $property, $currentValue, …). A shared instance
    // would leak state between usages in the same request.
    //
    // public() is needed so the Symfony test container can retrieve field
    // components via static::getContainer()->get(BoolField::class) etc.
    // Without it, non-shared services are invisible to the test container.
    $services->load(
        'Kachnitel\\EntityComponentsBundle\\Components\\Field\\',
        dirname(__DIR__) . '/src/Components/Field'
    )
        ->exclude([
            dirname(__DIR__) . '/src/Components/Field/Traits/',
            dirname(__DIR__) . '/src/Components/Field/EditabilityResolverInterface.php',
        ])
        ->share(false)
        ->public();

    // ── Editability resolver ───────────────────────────────────────────────────
    // DefaultEditabilityResolver allows editing any writable property.
    // Override this alias in your app's services.yaml to enforce custom policy:
    //
    //   Kachnitel\EntityComponentsBundle\Components\Field\EditabilityResolverInterface:
    //       alias: App\Field\MyEditabilityResolver
    //
    // kachnitel/admin-bundle registers AdminEditabilityResolver which adds
    // Symfony voter checks and #[AdminColumn] attribute support.
    $services->set(DefaultEditabilityResolver::class)
        ->autowire();

    $services->alias(EditabilityResolverInterface::class, DefaultEditabilityResolver::class);

    // ── Twig extension ─────────────────────────────────────────────────────────
    $services->set(ColorConverterExtension::class)
        ->tag('twig.extension');
    $services->set(UtilExtension::class)
        ->tag('twig.extension');

    // ── Doctrine event subscribers ─────────────────────────────────────────────
    // JoinTableNormalizerSubscriber rewrites interface-named join tables to use
    // the concrete class name configured in resolve_target_entities.
    // autoconfigure tags it as doctrine.event_subscriber automatically.
    $services->load(
        'Kachnitel\\EntityComponentsBundle\\Doctrine\\',
        dirname(__DIR__) . '/src/Doctrine'
    );
};
