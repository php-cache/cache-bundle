<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
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
	 * @var bool
	 */
	protected $keyMap = false;

	/**
	 * @var Connection
	 */
	protected $keyMapConnection = null;

	/**
	 * @param array    $config
	 * @param Registry $doctrine
	 *
	 * @throws \Exception
	 */
	public function __construct( $config, Registry $doctrine )
	{
		if ( empty( $config[ 'servers' ] ) ) {
			throw new \Exception( "Please configure the memcached extension. Missing Servers. " );
		}

		$this->setEnabled( $config[ 'enabled' ] );

		$persistent_id   = sha1( serialize( $config[ 'servers' ] ) );
		$this->memcached = new Memcached( $persistent_id );
		$this->addServers( $config[ 'servers' ] );

		$this->processOptions( $config[ 'options' ] );

		$this->setupKeyMapping( $config[ 'keyMap' ], $doctrine );
	}

	/**
	 * Sets up Key Mapping, if enabled
	 *
	 * Creates the necessary tables, if they arent there, and updates the service
	 *
	 * @param array            $configs
	 * @param Registry         $doctrine
	 *
	 * @throws \Exception
	 */
	private function setupKeyMapping( array $configs, Registry $doctrine )
	{
		if ( $configs[ 'enabled' ] ) {

			// Make sure the connection isn't empty
			if ( $configs[ 'connection' ] === '' ) {
				throw new \Exception( "Please specify a `connection` for the keyMap setting under memcached. " );
			}

			// Grab the connection from doctrine
			/** @var \Doctrine\DBAL\Connection $connection */
			$connection = $doctrine->getConnection( $configs[ 'connection' ] );

			// Create the table if it doesn't exist
			$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `memcached_key_map` (
  `id` BIGINT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cache_key` VARCHAR(255) NOT NULL,
  `memory_size` BIGINT(32) UNSIGNED,
  `lifeTime` INT(11) UNSIGNED NOT NULL,
  `expiration` DATETIME NOT NULL,
  `insert_date` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`cache_key`),
  INDEX (`expiration`),
  INDEX (`insert_date`)
) ENGINE=INNODB;
SQL;
			$connection->executeQuery( $sql );

			// Fetch the memcached service, set key mapping to enabled, and set the connection
			$this->setKeyMapEnabled( true )
				->setKeyMapConnection( $connection );
		}
	}

	/**
	 * Sets whether or not we are mapping keys
	 *
	 * @param bool $keyMap
	 *
	 * @return $this
	 */
	public function setKeyMapEnabled( $keyMap )
	{
		$this->keyMap = $keyMap;

		return $this;
	}

	/**
	 * @param array $servers
	 */
	public function addServers( array $servers )
	{
		if ( $this->isEnabled() ) {
			// Persistent Connections
			// Only add servers if the server list is empty
			if ( sizeof( $this->memcached->getServerList() ) === 0 ) {
				$this->memcached->addServers( $servers );
			}
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
	 * Puts data into the cache.
	 *
	 * @param string   $id       The cache id.
	 * @param string   $data     The cache entry/data.
	 * @param bool|int $lifeTime The lifetime. If != false, sets a specific lifetime for this
	 *                           cache entry (null => infinite lifeTime).
	 *
	 * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
	 */
	public function add( $id, $data, $lifeTime )
	{
		$this->addToKeyMap( $id, $data, $lifeTime );

		if ( $lifeTime > 30 * 24 * 3600 ) {
			$lifeTime = time() + $lifeTime;
		}

		return $this->memcached->add( $id, $data, $lifeTime );
	}

	/**
	 * Adds the given key to the key map
	 *
	 * @param $id
	 * @param $data
	 * @param $lifeTime
	 *
	 * @return bool|int
	 */
	private function addToKeyMap( $id, $data, $lifeTime )
	{
		if ( !$this->isKeyMapEnabled() ) {
			return false;
		}

		$data = array(
			'cache_key'   => $id,
			'memory_size' => $this->getPayloadSize( $data ),
			'lifeTime'    => $lifeTime,
			'expiration'  => date( 'Y-m-d H:i:s', strtotime( "now +{$lifeTime} seconds" ) ),
			'insert_date' => date( 'Y-m-d H:i:s' )
		);

		return $this->getKeyMapConnection()->insert( 'memcached_key_map', $data );
	}

	/**
	 * Gets whether or not mapping keys is enabled
	 *
	 * @return boolean
	 */
	public function isKeyMapEnabled()
	{
		return $this->keyMap;
	}

	/**
	 * Gets the memory size of the given variable
	 *
	 * @param $data
	 *
	 * @return int
	 */
	private function getPayloadSize( $data )
	{
		$start_memory = memory_get_usage();
		$data         = unserialize( serialize( $data ) );

		return memory_get_usage() - $start_memory - PHP_INT_SIZE * 8;
	}

	/**
	 * Gets the Key Mapping Doctrine Connection
	 *
	 * @return Connection|null
	 */
	public function getKeyMapConnection()
	{
		return $this->keyMapConnection;
	}

	/**
	 * Sets the Key Mapping Doctrine Connection
	 *
	 * @param Connection $keyMapConnection
	 *
	 * @return $this
	 */
	public function setKeyMapConnection( Connection $keyMapConnection )
	{
		$this->keyMapConnection = $keyMapConnection;

		return $this;
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
	 * @param $id
	 * @param $data
	 * @param $lifeTime
	 *
	 * @return bool
	 */
	public function set( $id, $data, $lifeTime )
	{
		$this->addToKeyMap( $id, $data, $lifeTime );

		if ( $lifeTime > 30 * 24 * 3600 ) {
			$lifeTime = time() + $lifeTime;
		}

		return $this->memcached->set( $id, $data, $lifeTime );
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
		return $this->set( $id, $data, (int)$lifeTime );
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
		$this->deleteFromKeyMap( $id );

		return $this->memcached->delete( $id );
	}

	private function deleteFromKeyMap( $id )
	{
		if ( !$this->isKeyMapEnabled() ) {
			return false;
		}

		return $this->getKeyMapConnection()->delete( 'memcached_key_map', array( 'cache_key' => $id ) );
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

	/**
	 * @param array $options
	 */
	private function processOptions( array $options )
	{

		$configs = array(
			'compression'          => Memcached::OPT_COMPRESSION,
			'serializer'           => Memcached::OPT_SERIALIZER,
			'prefix_key'           => Memcached::OPT_PREFIX_KEY,
			'hash'                 => Memcached::OPT_HASH,
			'distribution'         => Memcached::OPT_DISTRIBUTION,
			'libketama_compatible' => Memcached::OPT_LIBKETAMA_COMPATIBLE,
			'buffer_writes'        => Memcached::OPT_BUFFER_WRITES,
			'binary_protocol'      => Memcached::OPT_BINARY_PROTOCOL,
			'no_block'             => Memcached::OPT_NO_BLOCK,
			'tcp_no_delay'         => Memcached::OPT_TCP_NODELAY,
			'connect_timeout'      => Memcached::OPT_CONNECT_TIMEOUT,
			'retry_timeout'        => Memcached::OPT_RETRY_TIMEOUT,
			'send_timeout'         => Memcached::OPT_SEND_TIMEOUT,
			'recv_timeout'         => Memcached::OPT_RECV_TIMEOUT,
			'poll_timeout'         => Memcached::OPT_POLL_TIMEOUT,
			'server_failure_limit' => Memcached::OPT_SERVER_FAILURE_LIMIT
		);

		foreach ( $options as $name => $value ) {
			$this->memcached->setOption( $configs[ $name ], $value );
		}
	}

	/**
	 * @param \Closure|callable|mixed $payload
	 *
	 * @return mixed
	 */
	private function getDataFromPayload( $payload )
	{
		/** @var $payload \Closure|callable|mixed */
		if ( is_callable( $payload ) ) {
			if ( is_object( $payload ) && get_class( $payload ) == 'Closure' ) {
				return $payload();
			}

			return call_user_func( $payload );
		}

		return $payload;
	}
}

