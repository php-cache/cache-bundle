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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class LoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('cache.logging')) {
            return;
        }

        $config = $container->getParameter('cache.logging');
        if (!\is_array($config) || !isset($config['logger']) || !\is_string($config['logger'])) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds('cache.provider')) as $id) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();
            if (!\is_string($class) || !method_exists($class, 'setLogger')) {
                continue;
            }

            $definition->addMethodCall('setLogger', [new Reference($config['logger'])]);
        }
    }
}
