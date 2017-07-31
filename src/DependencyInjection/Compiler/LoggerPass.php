<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add logging to pool implementing LoggerAwareInterface.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LoggerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('cache.logging')) {
            return;
        }

        $config = $container->getParameter('cache.logging');
        if (!$config['enabled']) {
            return;
        }

        $serviceIds = $container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {
            $poolDefinition = $container->getDefinition($id);
            if (!method_exists($poolDefinition->getClass(), 'setLogger')) {
                continue;
            }
            $poolDefinition->addMethodCall('setLogger', [new Reference($config['logger'])]);
        }
    }
}
