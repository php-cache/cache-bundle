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
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RouterCompilerPass extends BaseCompilerPass
{
    /**
     * @return void
     */
    protected function prepare()
    {
        $router = $this->container->getParameter($this->getAlias().'.router');

        if (!$router['enabled']) {
            return;
        }

        $def = clone $this->container->findDefinition('router');
        $def->setClass('Cache\CacheBundle\Routing\Router');
        $def->addMethodCall('setCache', [new Reference($router['service_id'])]);
        $def->addMethodCall('setTtl', [$router['ttl']]);

        $this->container->setDefinition('cache.router', $def);
        $this->container->setAlias('router.alias', 'cache.router');

        if ($router['auto-register']) {
            $this->container->setAlias('router', 'cache.router');
        }
    }
}
