<?php
/**
 * @author    Aaron Scherer
 * @date      12/9/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Service;

use Doctrine\Common\Cache\Cache;

/**
 * Class CacheService
 *
 * @package Aequasi\Bundle\CacheBundle\Service
 * @method boolean flushAll() flushAll() Flushes all cache entries
 * @method boolean deleteAll() deleteAll() Deletes all cache entries
 * @method string  getNamespace() getNamespace() Retrieves the namespace that prefixes all cache ids.
 * @method boolean setNamespace() setNamespace( string $namespace ) Sets the namespace to prefix all cache ids wtih.
 */
class CacheService implements Cache
{

    /**
     * 60 Second Cache
     */
    const SIXTY_SECOND = 60;

    /**
     * 30 Minute Cache
     */
    const THIRTY_MINUTE = 1800;

    /**
     * 1 Hour Cache
     */
    const ONE_HOUR = 3600;

    /**
     * 6 Hour Cache
     */
    const SIX_HOUR = 21600;

    /**
     * Infinite Cache
     */
    const NO_EXPIRE = 0;

    /**
     * No Cache
     */
    const NO_CACHE = -1;

    /**
     * @var Cache
     */
    private $cache;

    private $logging = false;

    private $calls = array();

    protected $hosts = array();

    /**
     * Magic Extension of the Cache Providers
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call( $name, $arguments )
    {
        if (!method_exists( $this->cache, $name )) {
            throw new \InvalidArgumentException( sprintf(
                "%s is not a valid function of the %s cache type.",
                $name,
                get_class( $this->cache )
            ) );
        }

        return call_user_func_array( array( $this->cache, $name ), $arguments );
    }

    private function timeCall( $name, $arguments )
    {
        $start  = microtime( true );
        $result = call_user_func_array( array( $this->cache, $name ), $arguments );
        $time   = microtime( true ) - $start;

        $object = (object)compact( 'name', 'arguments', 'start', 'time', 'result' );

        return $object;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch( $id )
    {
        if ($this->isLogging()) {
            $call         = $this->timeCall( 'fetch', array( $id ) );
            $result       = $call->result;
            $call->result = '<DATA>';

            $this->calls[ ] = $call;

            return $result;
        }

        return $this->cache->fetch( $id );
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains( $id )
    {
        if ($this->isLogging()) {
            $call           = $this->timeCall( 'contains', array( $id ) );
            $this->calls[ ] = $call;

            return $call->result;
        }

        return $this->cache->contains( $id );
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The cache lifetime.
     *                         If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save( $id, $data, $lifeTime = self::NO_EXPIRE )
    {
        if ($this->isLogging()) {
            $call            = $this->timeCall( 'save', array( $id, $data, $lifeTime ) );
            $call->arguments = array( $id, '<DATA>', $lifeTime );
            $this->calls[ ]  = $call;

            return $call->result;
        }

        return $this->cache->save( $id, $data, $lifeTime );
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete( $id )
    {
        if ($this->isLogging()) {
            $call           = $this->timeCall( 'delete', array( $id ) );
            $this->calls[ ] = $call;

            return $call->result;
        }

        return $this->cache->delete( $id );
    }

    /**
     * Returns the $key from cache, if its there.
     * If its not there, it will use data to set the value.
     * If data is a closure, it will run the closure, and store the result.
     *
     * @param string         $id
     * @param callable|mixed $data
     * @param int            $lifeTime
     *
     * @return mixed
     */
    public function cache( $id, $data, $lifeTime = self::NO_EXPIRE )
    {
        if ($lifeTime === self::NO_CACHE) {
            return $this->getDataFromPayload( $data );
        }

        if ($this->contains( $id )) {
            return $this->fetch( $id );
        }

        $result = $this->getDataFromPayload( $data );
        $this->save( $id, $result, $lifeTime );
        return $result;
    }

    /**
     * Checks to see if $payload is callable, if it is, run it and return the data.
     * Otherwise, just return $payload
     *
     * @param $payload
     *
     * @return callable|mixed
     */
    private function getDataFromPayload( $payload )
    {
        /** @var $payload \Closure|callable|mixed */
        if (is_callable( $payload )) {
            if (is_object( $payload ) && get_class( $payload ) == 'Closure') {
                return $payload();
            }

            return call_user_func( $payload );
        }

        return $payload;
    }

    /**
     * Retrieves cached information from the data store.
     *
     * The server's statistics array has the following values:
     *
     * - <b>hits</b>
     * Number of keys that have been requested and found present.
     *
     * - <b>misses</b>
     * Number of items that have been requested and not found.
     *
     * - <b>uptime</b>
     * Time that the server is running.
     *
     * - <b>memory_usage</b>
     * Memory used by this server to store items.
     *
     * - <b>memory_available</b>
     * Memory allowed to use for storage.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }

    /**
     * @param \Doctrine\Common\Cache\Cache $cache
     */
    public function setCache( $cache )
    {
        $this->cache = $cache;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param boolean $logging
     */
    public function setLogging( $logging )
    {
        $this->logging = $logging;
    }

    /**
     * @return boolean
     */
    public function isLogging()
    {
        return $this->logging;
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * @param array $host
     */
    public function addHost(array $host)
    {
        $this->hosts[] = $host;
    }

    /**
     * @param array $hosts
     */
    public function setHosts(array $hosts)
    {
        $this->hosts = $hosts;
    }

    /**
     * @return array
     */
    public function getHosts()
    {
        return $this->hosts;
    }
}
