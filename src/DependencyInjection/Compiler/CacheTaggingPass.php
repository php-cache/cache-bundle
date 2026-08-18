<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Cache\CacheBundle\Command\CacheFlushCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CacheTaggingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $pools = [];
        if ($container->hasParameter('cache.provider_service_ids')) {
            $serviceIds = $container->getParameter('cache.provider_service_ids');
            if (\is_array($serviceIds)) {
                foreach ($serviceIds as $id) {
                    if (!\is_string($id) || !$container->has($id)) {
                        continue;
                    }

                    $pools[$id] = new Reference($id);
                    $definition = $container->findDefinition($id);
                    if (!$definition->hasTag('cache.provider')) {
                        $definition->addTag('cache.provider');
                    }
                }
            }
        }

        if (!$container->hasDefinition(CacheFlushCommand::class)) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds('cache.provider')) as $id) {
            $pools[$id] ??= new Reference($id);
        }
        foreach ($container->getAliases() as $id => $alias) {
            if ($alias->isPublic() && $container->findDefinition($id)->hasTag('cache.provider')) {
                $pools[$id] ??= new Reference($id);
            }
        }

        $container->getDefinition(CacheFlushCommand::class)->setArgument(
            0,
            ServiceLocatorTagPass::register($container, $pools, CacheFlushCommand::class),
        );
    }
}
