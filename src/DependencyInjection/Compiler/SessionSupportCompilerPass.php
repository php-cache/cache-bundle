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

use Cache\CacheBundle\Session\SessionHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SessionSupportCompilerPass.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class SessionSupportCompilerPass extends BaseCompilerPass
{
    /**
     *
     */
    protected function prepare()
    {
        // Check if session support is enabled
        if (!$this->container->hasParameter($this->getAlias().'.session')) {
            return;
        }

        // If there is no active session support, throw
        if (!$this->container->hasAlias('session.storage')) {
            throw new \Exception('Session cache support cannot be enabled if there is no session.storage service');
        }

        $this->enableSessionSupport($this->container->getParameter($this->getAlias().'.session'));
    }

    /**
     * Enables session support for memcached.
     *
     * @param array $config Configuration for bundle
     *
     * @throws InvalidConfigurationException
     */
    private function enableSessionSupport(array $config)
    {
        // calculate options
        $sessionOptions = $this->container->getParameter('session.storage.options');
        if (isset($sessionOptions['cookie_lifetime']) && !isset($config['cookie_lifetime'])) {
            $config['cookie_lifetime'] = $sessionOptions['cookie_lifetime'];
        }
        // load the session handler
        $definition = new Definition(SessionHandler::class);
        $definition->addArgument(new Reference($config['service_id']))
            ->addArgument($config);

        $this->container->setDefinition('cache.session_handler', $definition);

        $this->container->setAlias('session.handler', 'cache.session_handler');
    }
}
