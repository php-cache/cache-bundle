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

use Cache\CacheBundle\Cache\LoggingCachePool;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorate our cache.provider with a logger.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LoggingCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Check if logging support is enabled
        if (!$container->hasParameter('cache.logging')) {
            return;
        }

        $config     = $container->getParameter('cache.logging');
        $serviceIds = $container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {
            $def = $container->register($id.'.logger', LoggingCachePool::class);
            $def->addArgument(new Reference($id.'.logger.inner'))
                ->setDecoratedService($id, null, 10)
                ->addMethodCall('setLogger', [new Reference($config['logger'])])
                ->addMethodCall('setName', [$id])
                ->addMethodCall('setLevel', [$config['level']]);
        }
    }
}
