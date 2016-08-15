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

use Cache\AdapterBundle\DummyAdapter;
use Cache\CacheBundle\Cache\Recording\Factory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

        // Create a factory service
        $factoryId = 'cache.recorder_factory';
        $factory   = $container->register($factoryId, Factory::class);
        // Check if logging support is enabled
        if ($container->hasParameter('cache.logging')) {
            $config     = $container->getParameter('cache.logging');
            $factory->addArgument(new Reference($config['logger']));
            $factory->addArgument($config['level']);
        }

        $collectorDefinition = $container->getDefinition('cache.data_collector');
        $serviceIds          = $container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {

            // Get the pool definition and rename it.
            $poolDefinition = $container->getDefinition($id);
            $poolDefinition->setPublic(false);
            $container->setDefinition($id.'.inner', $poolDefinition);

            // Create a recording pool with a factory
            $recorderDefinition = $container->register($id, DummyAdapter::class);
            $recorderDefinition->setFactory([new Reference($factoryId), 'create']);
            $recorderDefinition->addArgument($id);
            $recorderDefinition->addArgument(new Reference($id.'.inner'));
            $recorderDefinition->setTags($poolDefinition->getTags());

            // Tell the collector to add the new instance
            $collectorDefinition->addMethodCall('addInstance', [$id, new Reference($id)]);
        }
    }
}
