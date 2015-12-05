<?php

/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Builder;

use Aequasi\Bundle\CacheBundle\Cache\LoggingCachePool;
use Aequasi\Cache\CachePool;
use Aequasi\Cache\DoctrineCacheBridge;
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
        'memcache'  => [
            'class'   => 'Memcache',
            'connect' => 'addServer'
        ],
        'memcached' => [
            'class'   => 'Aequasi\Bundle\CacheBundle\Cache\Memcached',
            'connect' => 'addServer'
        ],
        'redis'     => [
            'class'   => 'Redis',
            'connect' => 'connect'
        ]
    ];

    /**
     * {@inheritDoc}
     */
    protected function prepare()
    {
        $instances = $this->container->getParameter($this->getAlias().'.instance');

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
        $typeId = $this->getAlias().'.abstract.'.$instance['type'];
        if (!$this->container->hasDefinition($typeId)) {
            throw new InvalidConfigurationException(
                sprintf(
                    "`%s` is not a valid cache type. If you are using a custom type, make sure to add your service. ",
                    $instance['type']
                )
            );
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
        $serviceId = $this->getAlias().'.instance.'.$name;

        // Create the core doctrine cache class
        $coreName = $serviceId.'.core';
        $doctrine = $this->container->setDefinition(
            $coreName,
            new Definition($this->container->getParameter($typeId.'.class'))
        );
        $doctrine->addMethodCall('setNamespace', [$namespace])
            ->setPublic(false);

        // Create the CacheItemPoolInterface object, Logging or not
        $this->container->setDefinition(
            $serviceId,
            new Definition($this->getCachePoolClassName(), [new Reference($coreName)])
        );

        // Set up the simple alias  (aequasi_cache.instance.xxx => aequasi_cache.xxx)
        $this->container->setAlias($this->getAlias().'.'.$name, $serviceId);

        // Create the Doctrine/PSR-6 Bridge, for the doctrine cache piece
        $bridge = $this->container->setDefinition(
            $serviceId.'.bridge',
            new Definition(DoctrineCacheBridge::class, [new Reference($serviceId)])
        );
        $bridge->setPublic(false);

        return $doctrine;
    }

    /**
     * @param Definition $service
     * @param string     $name
     * @param array      $instance
     *
     * @return Boolean
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
                $directory = '%kernel.cache_dir%/doctrine/cache';
                if (null !== $instance['directory']) {
                    $directory = $instance['directory'];
                }
                $extension = is_null($instance['extension']) ? null : $instance['extension'];

                $service->setArguments([$directory, $extension]);

                return true;
            case 'mongo':
            case 'sqlite3':
            case 'sqlite':
            case 'riak':
            case 'chain':
                return false;
            default:
                return true;
        }
    }

    /**
     * Creates a cache instance
     *
     * @param Definition $service
     * @param string     $type
     * @param string     $id
     * @param array      $instance
     *
     * @return Boolean
     */
    public function createCacheInstance(Definition $service, $type, $id, array $instance)
    {
        if (empty($instance['id'])) {
            $cache = new Definition(self::$types[$type]['class']);

            // set memcached options first as they need to be set before the servers are added.
            if ($type === 'memcached') {
                if (!empty($instance['options']['memcached'])) {
                    foreach ($instance['options']['memcached'] as $option => $value) {
                        switch ($option) {
                            case 'serializer':
                            case 'hash':
                            case 'distribution':
                                $value = constant(
                                    sprintf('\Memcached::%s_%s', strtoupper($option), strtoupper($value))
                                );
                                break;
                        }
                        $cache->addMethodCall(
                            'setOption',
                            [constant(sprintf('\Memcached::OPT_%s', strtoupper($option))), $value]
                        );
                    }
                }
            }

            if (isset($instance['persistent']) && $instance['persistent'] !== false) {
                if ($instance['persistent'] !== true) {
                    $persistentId = $instance['persistent'];
                } else {
                    $persistentId = substr(md5(serialize($instance['hosts'])), 0, 5);
                }
                if ($type === 'memcached') {
                    $cache->setArguments([$persistentId]);
                }
                if ($type === 'redis') {
                    self::$types[$type]['connect'] = 'pconnect';
                }
            }

            foreach ($instance['hosts'] as $config) {
                $arguments = [
                    'host' => empty($config['host']) ? 'localhost' : $config['host'],
                    'port' => empty($config['port']) ? 11211 : $config['port']
                ];
                if ($type === 'memcached') {
                    $arguments[] = is_null($config['weight']) ? 0 : $config['weight'];
                } else {
                    $arguments[] = is_null($config['timeout']) ? 0 : $config['timeout'];
                    if (isset($persistentId)) {
                        $arguments[] = $persistentId;
                    }
                }

                $cache->addMethodCall(self::$types[$type]['connect'], $arguments);
            }
            unset($config);

            if ($type === 'redis') {
                if (isset($instance['auth_password']) && null !== $instance['auth_password']) {
                    $cache->addMethodCall('auth', [$instance['auth_password']]);
                }
                if (isset($instance['database'])) {
                    $cache->addMethodCall('select', [$instance['database']]);
                }
            }

            $this->container->setDefinition($id, $cache);
        } else {
            $id = $instance['id'];
        }
        $service->addMethodCall(sprintf('set%s', ucwords($type)), [new Reference($id)]);

        return true;
    }

    private function getCachePoolClassName()
    {
        return $this->container->getParameter('kernel.debug') ? LoggingCachePool::class : CachePool::class;
    }
}
