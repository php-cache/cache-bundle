<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Service;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Memcached;

/**
 * Class MemcachedService
 *
 * @package Aequasi\Bundle\MemcachedBundle\Service
 */
class MemcachedService extends CacheProvider implements Cache
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
	 * @var bool
	 */
	protected $enabled;

	/**
	 * @var Memcached
	 */
	protected $memcached;

	/**
	 * @param      $servers
	 * @param null $callback
	 */
	public function __construct( $servers, $options, $enabled = true )
	{
		if( empty( $servers ) ) {
			throw new \Exception( "Please configure the memcached extension. Missing Servers. " );
		}

		$this->setEnabled( $enabled );
		
		$persistent_id   = sha1( serialize( $servers ) );
		$this->memcached = new Memcached( $persistent_id );
		$this->addServers( $servers );

		foreach( $options as $option => $value ) {
			$this->memcached->setOption( $option, $value );
		}
	}

	/**
	 * @param array $servers
	 */
	public function addServers( array $servers )
	{
		if( false === $this->isEnabled() ) {
			return false;
		}
	
		// Persistent Connections
		// Only add servers if the server list is empty
		if ( sizeof( $this->memcached->getServerList() ) === 0 ) {
			$this->memcached->addServers( $servers );
		}
	}

	/**
	 * @param     $key
	 * @param     $payload
	 * @param int $time
	 *
	 * @return mixed
	 */
	public function cache( $key, $payload, $time = self::NO_EXPIRE )
	{
		if ( $this->isEnabled() && $time !== self::NO_CACHE ) {
			$result = $this->get( $key );
			if ( $result !== false ) {
				return $result;
			}
			$result = $this->getDataFromPayload( $payload );
			$this->add( $key, $result, $time );
		} else {
			$result = $this->getDataFromPayload( $payload );
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param mixed|\Closure|callable $payload
	 *
	 * @return mixed
	 */
	private function getDataFromPayload( $payload )
	{
		if ( is_callable( $payload ) ) {
			if ( is_object( $payload ) && get_class( $payload ) == 'Closure' ) {
				return $payload();
			}
			return call_user_func( $payload );
		}
		return $payload;
	}

	/**
	 * @param $id
	 * @param $data
	 * @param $lifeTime
	 *
	 * @return bool
	 */
	public function add( $id, $data, $lifeTime )
	{
		return $this->memcached->add( $id, $data, $lifeTime );
	}

	/**
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function setEnabled( $enabled = true )
	{
		$this->enabled = $enabled;

		return $this;
	}

	/**
	 * Gets the memcached instance used by the cache.
	 *
	 * @return Memcached
	 */
	public function getMemcached()
	{
		return $this->memcached;
	}

	/**
	 * Sets the memcache instance to use.
	 *
	 * @param Memcached $memcached
	 */
	public function setMemcached( Memcached $memcached )
	{
		$this->memcached = $memcached;
	}

	/**
	 * Fetches an entry from the cache.
	 *
	 * @param string $id cache id The id of the cache entry to fetch.
	 *
	 * @return string The cached data or FALSE, if no cache entry exists for the given id.
	 */
	protected function doFetch( $id )
	{
		return $this->get( $id );
	}

	/**
	 * Test if an entry exists in the cache.
	 *
	 * @param string $id cache id The cache id of the entry to check for.
	 *
	 * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
	 */
	protected function doContains( $id )
	{
		return ( false !== $this->get( $id ) );
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function get( $id )
	{
		return $this->memcached->get( $id );
	}

	/**
	 * Puts data into the cache.
	 *
	 * @param string   $id       The cache id.
	 * @param string   $data     The cache entry/data.
	 * @param bool|int $lifeTime The lifetime. If != false, sets a specific lifetime for this
	 *                           cache entry (null => infinite lifeTime).
	 *
	 * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
	 */
	protected function doSave( $id, $data, $lifeTime = false )
	{
		if ( $lifeTime > 30 * 24 * 3600 ) {
			$lifeTime = time() + $lifeTime;
		}

		return $this->set( $id, $data, (int)$lifeTime );
	}

	/**
	 * @param $id
	 * @param $data
	 * @param $lifeTime
	 *
	 * @return bool
	 */
	public function set( $id, $data, $lifeTime )
	{
		return $this->memcached->set( $id, $data, $lifeTime );
	}

	/**
	 * Deletes a cache entry.
	 *
	 * @param string $id cache id
	 *
	 * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
	 */
	protected function doDelete( $id )
	{
		return $this->memcached->delete( $id );
	}

	/**
	 * Deletes all cache entries.
	 *
	 * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
	 */
	protected function doFlush()
	{
		return $this->memcached->flush();
	}

	/**
	 * Retrieves cached information from data store
	 *
	 * @since   2.2
	 * @return  array An associative array with server's statistics if available, NULL otherwise.
	 */
	protected function doGetStats()
	{
		$stats   = $this->memcached->getStats();
		$servers = $this->memcached->getServerList();
		$key     = $servers[ 0 ][ 'host' ] . ':' . $servers[ 0 ][ 'port' ];
		$stats   = $stats[ $key ];

		return array(
			Cache::STATS_HITS              => $stats[ 'get_hits' ],
			Cache::STATS_MISSES            => $stats[ 'get_misses' ],
			Cache::STATS_UPTIME            => $stats[ 'uptime' ],
			Cache::STATS_MEMORY_USAGE      => $stats[ 'bytes' ],
			Cache::STATS_MEMORY_AVAILIABLE => $stats[ 'limit_maxbytes' ],
		);
	}
}

