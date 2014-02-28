<?php

/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Builder;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ServiceBuilder
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class ServiceBuilder extends BaseBuilder
{
    /**
     * Array of types, and their options
     *
     * @var array $types
     */
    protected static $types = [
        'memcache' => [
            'class' => 'Memcache',
            'connect' => 'addServer'
        ],
        'memcached' => [
            'class' => 'Aequasi\Bundle\CacheBundle\Cache\Memcached',
            'connect' => 'addServer'
        ],
        'redis' => [
            'class' => 'Redis',
            'connect' => 'connect'
        ]
    ];

    /**
     * {@inheritDoc}
     */
    protected function prepare()
    {
        $instances = $this->container->getParameter($this->getAlias() . '.instance');

        foreach ($instances as $name => $instance) {
            $this->buildInstance($name, $instance);
        }
    }

    /**
     * @param string $name
     * @param array  $instance
     *
     * @throws InvalidConfigurationException
     */
    private function buildInstance($name, array $instance)
    {
        $typeId = $this->getAlias() . '.abstract.' . $instance['type'];
        if (!$this->container->hasDefinition($typeId)) {
            throw new InvalidConfigurationException(sprintf(
                "`%s` is not a valid cache type. If you are using a custom type, make sure to add your service. ",
                $instance['type']
            ));
        }

        $service = $this->buildService($typeId, $name, $instance);

        $this->prepareCacheClass($service, $name, $instance);
    }

    /**
     * @param string $typeId
     * @param string $name
     * @param array  $instance
     *
     * @return Definition
     */
    private function buildService($typeId, $name, array $instance)
    {
        $namespace = is_null($instance['namespace']) ? $name : $instance['namespace'];

        $coreName = $this->getAlias() . '.instance.' . $name . '.core';
        $doctrine =
            $this->container->setDefinition(
                $coreName,
                new Definition($this->container->getParameter($typeId . '.class'))
            )
                ->addMethodCall('setNamespace', array($namespace))
                ->setPublic(false);
        $service  =
            $this->container->setDefinition(
                $this->getAlias() . '.instance.' . $name,
                new Definition($this->container->getParameter('aequasi_cache.service.class'))
            )
                ->addMethodCall('setCache', array(new Reference($coreName)))
                ->addMethodCall('setLogging', array($this->container->getParameter('kernel.debug')));

        if (isset($instance['hosts'])) {
            $service->addMethodCall('setHosts', array($instance['hosts']));
        }

        $alias = new Alias($this->getAlias() . '.instance.' . $name);
        $this->container->setAlias($this->getAlias() . '.' . $name, $alias);

        return $doctrine;
    }

    /**
     * @param Definition $service
     * @param string     $name
     * @param array      $instance
     */
    private function prepareCacheClass(Definition $service, $name, array $instance)
    {
        $type = $instance['type'];
        $id   = sprintf("%s.instance.%s.cache_instance", $this->getAlias(), $name);
        switch ($type) {
            case 'memcache':
            case 'memcached':
            case 'redis':
                return $this->createCacheInstance($service, $type, $id, $instance);
            case 'file_system':
            case 'php_file':
                $directory =
                    is_null($instance['directory']) ? '%kernel.cache_dir%/doctrine/cache' : $instance['directory'];
                $extension = is_null($instance['extension']) ? null : $instance['extension'];

                $service->setArguments(array($directory, $extension));
                break;
        }
    }

    /**
     * Creates a cache instance
     *
     * @param Definition $service
     * @param string     $type
     * @param string     $id
     * @param array      $instance
     */
    public function createCacheInstance(Definition $service, $type, $id, array $instance)
    {
        if (empty($instance['id'])) {
            $cache = new Definition(self::$types[$type]['class']);

            if (isset($instance['persistent'])) {
                if ($type === 'memcached') {
                    $cache->setArguments(array(serialize($instance['hosts'])));
                }
                if ($type === 'redis') {
                    self::$types[$type]['connect'] = 'pconnect';
                }
            }

            foreach ($instance['hosts'] as $config) {
                $host    = empty($config['host']) ? 'localhost' : $config['host'];
                $port    = empty($config['port']) ? 11211 : $config['port'];
                if ($type === 'memcached') {
                    $thirdParam = is_null($config['weight']) ? 0 : $config['weight'];
                } else {
                    $thirdParam = is_null($config['timeout']) ? 0 : $config['timeout'];
                }

                $cache->addMethodCall(self::$types[$type]['connect'], array($host, $port, $thirdParam));
            }
            unset($config);

            if ($type === 'redis') {
                if (isset($instance['auth_password']) && null !== $instance['auth_password']) {
                    $cache->addMethodCall('auth', array($instance['auth_password']));
                }
                if (isset($instance['database'])) {
                    $cache->addMethodCall('select', array($instance['database']));
                }
            }

            $this->container->setDefinition($id, $cache);
        } else {
            $id = $instance['id'];
        }
        $service->addMethodCall(sprintf('set%s', ucwords($type)), array(new Reference($id)));
    }
}
