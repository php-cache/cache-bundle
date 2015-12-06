<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection;

use Cache\CacheBundle\Cache\LoggingCachePool;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class AequasiCacheExtension
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheExtension extends Extension
{
    /**
     * Loads the configs for Cache and puts data into the container
     *
     * @param array            $configs   Array of configs
     * @param ContainerBuilder $container Container Object
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        if ($container->getParameter('kernel.debug')) {
            $this->transformLoggableCachePools($container);

            $container->register('data_collector.cache', CacheDataCollector::class)
                ->addTag('data_collector', ['template' => CacheDataCollector::TEMPLATE, 'id' => 'cache']);
        }

        foreach (['router', 'session', 'doctrine'] as $section) {
            if ($container[$section]['enabled']) {
                $container->setParameter($this->getAlias().'.'.$section, $config[$section]);
            }
        }

    }

    public function getAlias()
    {
        return 'cache';
    }

    private function transformLoggableCachePools(ContainerBuilder $container)
    {
        $serviceIds = $container->findTaggedServiceIds('cache.provider');
        foreach (array_keys($serviceIds) as $id) {
            $container->setDefinition($id.'.logged', $container->findDefinition($id));
            $def = $container->register($id.'.logger', LoggingCachePool::class);
            $def->addArgument(new Reference($id));

            $container->setAlias($id, $id.'.logger');
        }
    }
}
