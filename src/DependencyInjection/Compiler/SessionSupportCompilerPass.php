<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Enable the session support by rewriting the "session.handler" alias.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class SessionSupportCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        // Check if session support is enabled
        if (!$container->hasParameter('cache.session')) {
            return;
        }

        // If there is no active session support, throw
        if (!$container->hasAlias('session.storage')) {
            throw new \Exception('Session cache support cannot be enabled if there is no session.storage service');
        }

        $container->setAlias('session.handler', 'cache.service.session');
    }
}
