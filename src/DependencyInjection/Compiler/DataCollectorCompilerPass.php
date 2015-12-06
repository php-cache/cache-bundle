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

use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DataCollectorCompilerPass
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DataCollectorCompilerPass extends BaseCompilerPass
{
    /**
     * {@inheritDoc}
     */
    protected function prepare()
    {
        $instances = $this->container->getParameter($this->getAlias() . '.instance');

        $definition = $this->container->getDefinition('data_collector.cache');

        foreach (array_keys($instances) as $name) {
            $instance = new Reference(sprintf("%s.instance.%s", $this->getAlias(), $name));
            $definition->addMethodCall('addInstance', array($name, $instance));
        }

        $this->container->setDefinition('data_collector.cache', $definition);
    }
}
