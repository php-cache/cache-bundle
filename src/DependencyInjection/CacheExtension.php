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

use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\Routing\RouterListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class CacheExtension
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
            $container->register('data_collector.cache', CacheDataCollector::class)
                ->addTag('data_collector', ['template' => CacheDataCollector::TEMPLATE, 'id' => 'cache']);
        }

        foreach (['router', 'session', 'doctrine'] as $section) {
            if ($config[$section]['enabled']) {
                $container->setParameter('cache.'.$section, $config[$section]);
            }
        }

        if ($config['router']['enabled']) {
            $container->register('cache.router_listener', RouterListener::class)
                ->addArgument(new Reference($config['router']['service_id']))
                ->addArgument($config['router']['ttl'])
                ->addTag('kernel.event_listener', ['event'=>'kernel.request', 'method'=>'onBeforeRouting', 'priority'=>33])
                ->addTag('kernel.event_listener', ['event'=>'kernel.request', 'method'=>'onAfterRouting', 'priority'=>31]);
        }

    }

    public function getAlias()
    {
        return 'cache';
    }
}
