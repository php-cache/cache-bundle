<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Registry;

abstract class KeyMapCacheDriver
{

	/**
	 * @var bool
	 */
	protected $keyMap = false;

	/**
	 * @var Connection
	 */
	protected $keyMapConnection = null;
	
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
				throw new \Exception( "Please specify a `connection` for the keyMap setting. " );
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
	 * Gets whether or not mapping keys is enabled
	 *
	 * @return boolean
	 */
	public function getKeyMapEnabled()
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
	 * @return string
	 */
	abstract public function getKeyMapTable();

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

		return $this->getKeyMapConnection()->insert( $this->getKeyMapTable(), $data );
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

		return $this->getKeyMapConnection()->delete( $this->getKeyMapTable(), array( 'cache_key' => $id ) );
	}

	/**
	 * @return bool|int
	 */
	private function truncateKeyMap( )
	{
		if ( !$this->isKeyMapEnabled() ) {
			return false;
		}

		return $this->getKeyMapConnection()->executeQuery( sprintf( 'TRUNCATE %s', $this->getKeyMapTable() ) );
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
