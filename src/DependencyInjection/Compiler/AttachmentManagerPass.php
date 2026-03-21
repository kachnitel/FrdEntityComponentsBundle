<?php

declare(strict_types=1);

namespace Kachnitel\EntityComponentsBundle\DependencyInjection\Compiler;

use Kachnitel\EntityComponentsBundle\Components\AttachmentManager;
use Kachnitel\EntityComponentsBundle\Interface\FileHandlerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Removes AttachmentManager from the container when no FileHandlerInterface
 * implementation is registered.
 *
 * This allows the bundle to be used without providing a file handler — apps
 * that only need TagManager, CommentsManager, or the field components are not
 * forced to implement FileHandlerInterface just to satisfy autowiring.
 *
 * Apps that do register a FileHandlerInterface get AttachmentManager for free.
 */
final class AttachmentManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has(FileHandlerInterface::class)) {
            return;
        }

        if ($container->hasDefinition(AttachmentManager::class)) {
            $container->removeDefinition(AttachmentManager::class);
        }
    }
}
