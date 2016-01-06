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

use Cache\CacheBundle\Cache\RecordingCachePool;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DataCollectorCompilerPass.
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
        $collectorDefinition = $container->getDefinition('cache.data_collector');
        $serviceIds          = $container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {

            // Creating a LoggingCachePool instance, and passing it the new definition from above
            $def = $container->register($id.'.recorder', RecordingCachePool::class);
            $def->addArgument(new Reference($id.'.recorder.inner'))
                ->setDecoratedService($id, null, 10);

            // Tell the collector to add the new logger
            $collectorDefinition->addMethodCall('addInstance', [$id, new Reference($id.'.recorder')]);
        }
    }
}
