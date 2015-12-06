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

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SessionSupportCompilerPass
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
        // If there is no active session support, return
        if (!$this->container->hasAlias('session.storage')) {
            return;
        }

        // If the aequasi.cache.session_handler service is loaded set the alias
        if ($this->container->hasParameter($this->getAlias() . '.session')) {
            $this->enableSessionSupport($this->container->getParameter($this->getAlias() . '.session'));
        }
    }

    /**
     * Enables session support for memcached
     *
     * @param array $config Configuration for bundle
     *
     * @throws InvalidConfigurationException
     */
    private function enableSessionSupport(array $config)
    {
        if (empty($config['instance'])) {
            throw new InvalidConfigurationException("Instance must be passed under the `session` config.");
        }

        $instance = $config['instance'];
        $instances = $this->container->getParameter($this->getAlias() . '.instance');

        if (null === $instance) {
            return;
        }
        if (!isset($instances[$instance])) {
            throw new InvalidConfigurationException(sprintf(
                "Failed to hook into the session. The instance \"%s\" doesn't exist!",
                $instance
            ));
        }

        // calculate options
        $sessionOptions = $this->container->getParameter('session.storage.options');
        if (isset($sessionOptions['cookie_lifetime']) && !isset($config['cookie_lifetime'])) {
            $config['cookie_lifetime'] = $sessionOptions['cookie_lifetime'];
        }
        // load the session handler
        $definition =
            new Definition($this->container->getParameter(sprintf('%s.session.handler.class', $this->getAlias())));
        $definition->addArgument(new Reference(sprintf('%s.instance.%s.bridge', $this->getAlias(), $instance)))
            ->addArgument($config);

        $this->container->setDefinition(sprintf('%s.session_handler', $this->getAlias()), $definition);
        $this->container->setAlias('cache.session_handler', sprintf('%s.session_handler', $this->getAlias()));

        $this->container->setAlias('session.handler', 'cache.session_handler');
    }
}
