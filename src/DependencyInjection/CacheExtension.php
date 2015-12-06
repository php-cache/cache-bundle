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
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        if ($container->getParameter('kernel.debug')) {
            $loader->load('collector.yml');
        }

        $container->setParameter($this->getAlias() . '.instance', $config['instances']);
        new Builder\ServiceBuilder($container);

        if ($config['router']['enabled']) {
            $container->setParameter($this->getAlias() . '.router', $config['router']);
        }

        if ($config['session']['enabled']) {
            $container->setParameter($this->getAlias() . '.session', $config['session']);
        }

        if ($config['doctrine']['enabled']) {
            $container->setParameter($this->getAlias() . '.doctrine', $config['doctrine']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
      return new Configuration($container->getParameter('kernel.debug'));
    }

    public function getAlias()
    {
        return 'cache';
    }
}
