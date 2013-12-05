<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Session;

use Aequasi\Bundle\CacheBundle\Cache\LoggingMemcachedInterface as Memcached;

/**
 * MemcachedSessionHandler.
 *
 * Memcached based session storage handler based on the LoggingMemcached class
 * provided by the PHP memcached extension.
 *
 */
class MemcachedSessionHandler implements \SessionHandlerInterface
{

	/**
	 * @var Memcached Memcached driver.
	 */
	private $memcached;

	/**
	 * @var integer Time to live in seconds
	 */
	private $ttl;

	/**
	 * @var string Key prefix for shared environments.
	 */
	private $prefix;

	/**
	 * Constructor.
	 *
	 * List of available options:
	 *  * prefix: The prefix to use for the memcached keys in order to avoid collision
	 *  * expiretime: The time to live in seconds
	 *
	 * @param Memcached  $memcached A \Memcached instance
	 * @param array      $options   An associative array of Memcached options
	 *
	 * @throws \InvalidArgumentException When unsupported options are passed
	 */
	public function __construct( Memcached $memcached, array $options = array() )
	{
		$this->memcached = $memcached;

		if ( $diff = array_diff( array_keys( $options ), array( 'prefix', 'expiretime' ) ) ) {
			throw new \InvalidArgumentException( sprintf(
				'The following options are not supported "%s"',
				implode( ', ', $diff )
			) );
		}

		$this->ttl    = isset( $options[ 'expiretime' ] ) ? (int)$options[ 'expiretime' ] : 86400;
		$this->prefix = isset( $options[ 'prefix' ] ) ? $options[ 'prefix' ] : 'sf2s';
	}

	/**
	 * {@inheritDoc}
	 */
	public function open( $savePath, $sessionName )
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close()
	{
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function read( $sessionId )
	{
		return $this->memcached->get( $this->prefix . $sessionId ) ? : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function write( $sessionId, $data )
	{
		return $this->memcached->set( $this->prefix . $sessionId, $data, time() + $this->ttl );
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy( $sessionId )
	{
		return $this->memcached->delete( $this->prefix . $sessionId );
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc( $lifetime )
	{
		// not required here because memcached will auto expire the records anyhow.
		return true;
	}
}
