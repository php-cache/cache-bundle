<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Cache;

use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Memcached;

/**
 * Class to encapsulate PHP Memcached object for unit tests and to add logging in logging mode
 */
class LoggingMemcached implements LoggingMemcachedInterface
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
	 * @var array
	 */
	protected $calls;

	/**
	 * @var bool
	 */
	protected $initialize;

	/**
	 * @var bool
	 */
	protected $logging;

	/**
	 * @var bool
	 */
	protected $keyMap = false;

	/**
	 * @var Connection
	 */
	protected $keyMapConnection = null;

	/**
	 * @var \Memcached
	 */
	protected $memcached;

	/**
	 * Constructor instantiates and stores Memcached object
	 *
	 * @param bool $enabled      Are we caching?
	 * @param bool $logging      Are we logging?
	 * @param null $persistentId Are we persisting?
	 */
	public function __construct( $enabled, $logging = false, $persistentId = null )
	{
		$this->enabled = $enabled;
		$this->calls   = array();
		$this->logging = $logging;
		if ( $persistentId ) {
			$this->memcached  = new \Memcached( $persistentId );
			$this->initialize = count( $this->getServerList() ) == 0;
		} else {
			$this->memcached  = new \Memcached();
			$this->initialize = true;
		}
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
	public function setupKeyMap( array $configs, Registry $doctrine )
	{
		if ( $configs[ 'enabled' ] ) {

			// Make sure the connection isn't empty
			if ( $configs[ 'connection' ] === '' ) {
				throw new \Exception( "Please specify a `connection` for the keyMap setting under memcached. " );
			}

			// Grab the connection from doctrine
			/** @var \Doctrine\DBAL\Connection $connection */
			$connection = $doctrine->getConnection( $configs[ 'connection' ] );

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
			$this->set( $key, $result, $time );
		} else {
			$result = $this->getDataFromPayload( $payload );
		}

		return $result;
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
	 * Get the logged calls for this Memcached object
	 *
	 * @return array Array of calls made to the Memcached object
	 */
	public function getLoggedCalls()
	{
		return $this->calls;
	}

	/**
	 * @param $enabled
	 *
	 * @return $this
	 */
	public function setEnabled( $enabled )
	{
		$this->enabled = $enabled;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @return bool
	 */
	public function hasError()
	{
		return $this->memcached->getResultCode() !== Memcached::RES_SUCCESS;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->memcached->getResultMessage();
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	function __call( $name, $arguments )
	{
		return $this->processRequest( $name, $arguments );
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	protected function processRequest( $name, $arguments )
	{

		if ( $this->logging ) {
			$start          = microtime( true );
			$result         = call_user_func_array( array( $this->memcached, $name ), $arguments );
			$time           = microtime( true ) - $start;
			$this->calls[ ] = (object)compact( 'start', 'time', 'name', 'arguments', 'result' );
		} else {
			$result = call_user_func_array( array( $this->memcached, $name ), $arguments );
		}

		if( in_array( $name, array( 'add', 'set' ) ) ) {
			$this->addToKeyMap( $arguments[ 0 ], $arguments[ 1 ], $arguments[ 2 ] );
		}
		if( $name == 'delete' ) {
			$this->deleteFromKeyMap( $arguments[ 0 ] );
		}
		if( $name == 'flush' ) {
			$this->truncateKeyMap( );
		}

		return $result;
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
		if ( $lifeTime === null ) {
			unset( $data[ 'lifeTime' ], $data[ 'expiration' ] );
		}

		return $this->getKeyMapConnection()->insert( 'memcached_key_map', $data );
	}

	/**
	 * @param $id
	 *
	 * @return bool|int
	 */
	private function deleteFromKeyMap( $id )
	{
		if ( !$this->isKeyMapEnabled() ) {
			return false;
		}

		return $this->getKeyMapConnection()->delete( 'memcached_key_map', array( 'cache_key' => $id ) );
	}

	/**
	 * @return bool|int
	 */
	private function truncateKeyMap( )
	{
		if ( !$this->isKeyMapEnabled() ) {
			return false;
		}

		return $this->getKeyMapConnection()->executeQuery( 'TRUNCATE memcached_key_map' );
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
}