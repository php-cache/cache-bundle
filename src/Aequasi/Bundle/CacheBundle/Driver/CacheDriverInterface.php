<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\Driver;

interface CacheDriverInterface
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
	 * Constructor for the Cache Driver. Should flag enabled, and debug (logging).
	 * 
	 * For instances like memcached, constructor will add servers and set options.
	 *
	 * @param bool  $enabled Whether or not this driver is enabled
	 * @param bool  $debug Whether or not the kernel.debug env variable is true or false
	 * @param array $options Array of options
	 *
	 */
	public function __construct( $enabled, $debug, array $options );

	/**
	 * Runs the call on the cache function
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 */
	protected function __doCall( $function, $arguments );
	
	/**
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function setEnabled( $enabled );

	/**
	 * @return boolean
	 */
	public function getEnabled();

	/**
	 * @param bool $debug
	 *
	 * @return $this
	 */
	public function setDebug( $debug );

	/**
	 * return bool
	 */
	public function getDebug();

	/**
	 * @param bool $initialized
	 *
	 * @return $this
	 */
	public function setInitialized( $initialized );

	/**
	 * return bool
	 */
	public function getInitialized();

	/**
	 * @param array $options
	 *
	 * @return $this
	 */
	public function setOptions( array $options );

	/**
	 * @return array
	 */
	public function getOptions( );
	
	/**
	 * Get the logged calls for this object
	 *
	 * @return array Array of calls made to this object
	 */
	public function getLoggedCalls();
}
