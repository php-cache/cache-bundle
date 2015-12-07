<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Cache\CacheBundle\Cache\LoggingCachePool;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DataCollectorCompilerPass
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DataCollectorCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    protected function prepare()
    {
        $collectorDefinition = $this->container->getDefinition('data_collector.cache');
        $serviceIds = $this->container->findTaggedServiceIds('cache.provider');

        foreach (array_keys($serviceIds) as $id) {

            // Duplicating definition to $originalServiceId.logged
            $this->container->setDefinition($id.'.logged', $this->container->findDefinition($id));

            // Creating a LoggingCachePool instance, and passing it the new definition from above
            $def = $this->container->register($id.'.logger', LoggingCachePool::class);
            $def->addArgument(new Reference($id.'.logged'));

            // Overwrite the original service id with the new LoggingCachePool instance
            $this->container->setAlias($id, $id.'.logger');
            $collectorDefinition->addMethodCall('addInstance', [$id, new Reference($id.'.logger')]);
        }
    }
}
