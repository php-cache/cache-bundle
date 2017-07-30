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

use Cache\CacheBundle\DataCollector\DecoratingFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Inject a data collector to all the cache services to be able to get detailed statistics.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DataCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('cache.data_collector')) {
            return;
        }

        $proxyFactory        = $container->get('cache.proxy_factory');
        $collectorDefinition = $container->getDefinition('cache.data_collector');
        $serviceIds          = $container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {

            // Get the pool definition and rename it.
            $poolDefinition = $container->getDefinition($id);
            if (null === $poolDefinition->getFactory()) {
                // Just replace the class
                $proxyClass = $proxyFactory->createProxy($poolDefinition->getClass(), $file);
                $poolDefinition->setClass($proxyClass);
                $poolDefinition->setFile($file);
                $poolDefinition->addMethodCall('__setName', [$id]);
            } else {
                // Create a new ID for the original service
                $innerId = $id.'.inner';
                $container->setDefinition($innerId, $poolDefinition);

                // Create a new definition.
                $decoratedPool = new Definition($poolDefinition->getClass());
                $decoratedPool->setFactory([new Reference('cache.decorating_factory'), 'create']);
                $decoratedPool->setArguments([new Reference($innerId)]);
                $container->setDefinition($id, $decoratedPool);
                $decoratedPool->addMethodCall('__setName', [$id]);
            }

            // Tell the collector to add the new instance
            $collectorDefinition->addMethodCall('addInstance', [$id, new Reference($id)]);
        }
    }
}
