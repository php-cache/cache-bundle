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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheExtension extends Extension
{
    /**
     * Loads the configs for Cache and puts data into the container.
     *
     * @param array            $configs   Array of configs
     * @param ContainerBuilder $container Container Object
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        foreach (['router', 'session', 'doctrine'] as $section) {
            if ($config[$section]['enabled']) {
                $container->setParameter('cache.'.$section, $config[$section]);
            }
        }

        if ($config['router']['enabled']) {
            $container->getDefinition('cache.router_listener')
                ->replaceArgument(0, new Reference($config['router']['service_id']))
                ->replaceArgument(1, $config['router']['ttl']);
        } else {
            $container->removeDefinition('cache.router_listener');
        }

        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition('data_collector.cache');
        }
    }

    public function getAlias()
    {
        return 'cache';
    }
}
