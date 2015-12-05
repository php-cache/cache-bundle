<?php

/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RouterCompilerPass extends BaseCompilerPass
{
    /**
     * @return void
     */
    protected function prepare()
    {
        $router = $this->container->getParameter($this->getAlias() . '.router');

        if (!$router['enabled']) {
            return;
        }
        $instance = $router['instance'];

        $def = $this->container->findDefinition('router');
        $def->setClass('Aequasi\Bundle\CacheBundle\Routing\Router');
        $def->addMethodCall('setCache', [new Reference(sprintf('aequasi_cache.instance.%s', $instance))]);
    }
}
