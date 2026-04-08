<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler;

use Kachnitel\EntityComponentsBundle\Doctrine\JoinTableNormalizerSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads doctrine.orm.resolve_target_entities from the container config and
 * injects the full interface → concrete class mapping into
 * {@see JoinTableNormalizerSubscriber} at compile time.
 *
 * This allows the subscriber to rewrite join table names using the actual
 * concrete class name rather than the interface name, e.g.:
 *   product_attachment_interface → product_uploaded_file
 *   tag_interface_id             → tag_id
 */
final class JoinTableNormalizerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(JoinTableNormalizerSubscriber::class)) {
            return;
        }

        $container
            ->getDefinition(JoinTableNormalizerSubscriber::class)
            ->setArgument('$resolvedEntities', $this->collectResolvedEntities($container));
    }

    /**
     * Merges all doctrine extension configs (there may be multiple from
     * different bundles/files that Symfony has not yet merged) and extracts
     * the resolve_target_entities map.
     *
     * @return array<string, string>
     */
    private function collectResolvedEntities(ContainerBuilder $container): array
    {
        $resolved = [];

        foreach ($container->getExtensionConfig('doctrine') as $config) {
            $entries = $config['orm']['resolve_target_entities']
                    ?? $config['orm']['resolve_target_entity']
                    ?? [];

            foreach ($entries as $interface => $concrete) {
                $resolved[$interface] = $concrete;
            }
        }

        return $resolved;
    }
}
