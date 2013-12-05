<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Registry;

interface KeyMapCacheDriverInterface
{
	
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
	public function setupKeyMap( array $configs, Registry $doctrine );
	
	/**
	 * Sets whether or not we are mapping keys
	 *
	 * @param bool $keyMap
	 *
	 * @return $this
	 */
	public function setKeyMapEnabled( $keyMap );
	
	/**
	 * Gets whether or not mapping keys is enabled
	 *
	 * @return boolean
	 */
	public function getKeyMapEnabled();

	/**
	 * Gets the Key Mapping Doctrine Connection
	 *
	 * @return Connection|null
	 */
	public function getKeyMapConnection();

	/**
	 * Sets the Key Mapping Doctrine Connection
	 *
	 * @param Connection $keyMapConnection
	 *
	 * @return $this
	 */
	public function setKeyMapConnection( Connection $keyMapConnection );
	
	/**
	 * @return string
	 */
	public function getKeyMapTable();
	
	/**
	 * Adds the given key to the key map
	 *
	 * @param $id
	 * @param $data
	 * @param $lifeTime
	 *
	 * @return bool|int
	 */
	protected function addToKeyMap( $id, $data, $lifeTime );

	/**
	 * @param $id
	 *
	 * @return bool|int
	 */
	protected function deleteFromKeyMap( $id );

	/**
	 * @return bool|int
	 */
	protected function truncateKeyMap( );

}
