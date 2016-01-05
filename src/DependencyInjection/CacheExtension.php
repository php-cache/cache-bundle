<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
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

        // Make sure config values are in the parameters
        foreach (['router', 'session', 'doctrine', 'logging'] as $section) {
            if ($config[$section]['enabled']) {
                $container->setParameter('cache.'.$section, $config[$section]);
            }
        }

        if ($config['router']['enabled']) {
            $loader->load('router.yml');
            $container->getDefinition('cache.router')
                ->setDecoratedService('router', null, 10)
                ->replaceArgument(0, new Reference($config['router']['service_id']))
                ->replaceArgument(2, $config['router']['ttl']);
        }

        if ($container->getParameter('kernel.debug')) {
            $loader->load('data-collector.yml');
        }

        $serviceIds = [];
        $this->findServiceIds($config, $serviceIds);
        $container->setParameter('cache.provider.serviceIds', $serviceIds);
    }

    /**
     * Find service ids that we configured.
     *
     * @param array $config
     * @param array $serviceIds
     */
    protected function findServiceIds(array $config, array &$serviceIds)
    {
        foreach ($config as $name => $value) {
            if (is_array($value)) {
                $this->findServiceIds($value, $serviceIds);
            } elseif ($name === 'service_id') {
                $serviceIds[] = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'cache';
    }
}
