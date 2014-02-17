<?php
/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DataCollectorCompilerPass
 *
 * @package Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler
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
