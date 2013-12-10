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
 */
class CacheService implements Cache
{

	/**
	 * @var Cache
	 */
	private $cache;

	private $logging = false;

	private $calls = array();

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
	function fetch( $id )
	{
		if( $this->isLogging() ) {
			$call = $this->timeCall( 'fetch', array( $id ) );
			$result = $call->result;
			$call->result = '<DATA>';

			$this->calls[] = $call;

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
	function contains( $id )
	{
		if( $this->isLogging() ) {
			$call = $this->timeCall( 'contains', array( $id ) );
			$this->calls[ ]  = $call;

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
	function save( $id, $data, $lifeTime = 0 )
	{
		if( $this->isLogging() ) {
			$call = $this->timeCall( 'save', array( $id, $data, $lifeTime ) );
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
	function delete( $id )
	{
		if( $this->isLogging() ) {
			$call = $this->timeCall( 'delete', array( $id ) );
			$this->calls[ ]  = $call;

			return $call->result;
		}

		return $this->cache->delete( $id );
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
	function getStats()
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
}