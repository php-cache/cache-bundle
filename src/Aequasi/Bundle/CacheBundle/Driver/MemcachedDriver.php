<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\Driver;

class MemcachedDriver extends KeyMapCacheDriver
{
	/**
	 * @var \Memcached
	 */
	protected $memcached;
	
	/**
	 * Initialize the Cache Driver
	 *
	 * @return void
	 */
	protected function initialize()
	{
		$persistent = $this->getOptions()->get( 'persistent', false );
		$serverList = $this->getOptions()->get( 'servers' );

		if ( $persistent ) {
			$persistendId = serialize( $serverList );
			$this->memcached  = new \Memcached( $persistentId );
			if( count( $this->memcached->getServerList() ) === 0 ) {
				$this->addServers( $serverList );
			}
		} else {
			$this->memcached  = new \Memcached();
			$this->addServers( $serverList );
		}
		

		return true;
	}

	protected function __doCall( $function, $arguments )
	{
		return call_user_func_array( array( $this->memcached, $function ), $arguments );
	}
	
	public function getKeyMapTable( )
	{
		return 'memcached_key_map';
	}
}
