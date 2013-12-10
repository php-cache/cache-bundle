<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Session;

use Doctrine\Common\Cache\Cache;

/**
 * Class SessionHandler
 *
 * @package Aequasi\Bundle\CacheBundle\Session
 */
class SessionHandler implements \SessionHandlerInterface
{

	/**
	 * @var Cache Cache driver.
	 */
	private $cache;

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
	 *  * prefix: The prefix to use for the cache keys in order to avoid collision
	 *  * expiretime: The time to live in seconds
	 *
	 * @param Cache  $cache A Cache instance
	 * @param array  $options   An associative array of cache options
	 *
	 * @throws \InvalidArgumentException When unsupported options are passed
	 */
	public function __construct( Cache $cache, array $options = array() )
	{
		$this->cache = $cache;

		$this->ttl    = isset( $options[ 'cookie_lifetime' ] ) ? (int)$options[ 'cookie_lifetime' ] : 86400;
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
		return $this->cache->fetch( $this->prefix . $sessionId ) ? : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function write( $sessionId, $data )
	{
		return $this->cache->save( $this->prefix . $sessionId, $data, time() + $this->ttl );
	}

	/**
	 * {@inheritDoc}
	 */
	public function destroy( $sessionId )
	{
		return $this->cache->delete( $this->prefix . $sessionId );
	}

	/**
	 * {@inheritDoc}
	 */
	public function gc( $lifetime )
	{
		// not required here because cache will auto expire the records anyhow.
		return true;
	}
}
