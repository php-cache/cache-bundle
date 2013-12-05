<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\Driver;

use Symfony\Component\HttpFoundation\ParameterBag;

abstract class CacheDriver implements CacheDriverInterface
{
	/**
	 * @var bool $enabled
	 */
	protected $enabled = false;

	/**
	 * @var bool $debug
	 */
	protected $debug = false;

	/**
	 * @var ParameterBag $options
	 */
	protected $options;

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
	 * Constructor for the Cache Driver. Should flag enabled, and debug (logging).
	 * 
	 * For instances like memcached, constructor will add servers and set options.
	 *
	 * @param bool  $enabled Whether or not this driver is enabled
	 * @param bool  $debug Whether or not the kernel.debug env variable is true or false
	 * @param array $options Array of options
	 *
	 */
	final public function __construct( $enabled, $debug, array $options )
	{
		$this->setEnabled( $enabled );
		$this->setDebug( $debug );
		$this->setOptions( $options );
	
		$this->setInitialized( $this->initialize() );
	}

	/**
	 * Initialize the Cache Driver
	 *
	 * @return bool
	 */
	abstract protected function initialize();

	/**
	 * Runs the call on the cache function
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 */
	abstract protected function __doCall( $function, $arguments );
	 
	
	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments )
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

		if ( $this->debug ) {
			$start          = microtime( true );
			$result         = $this->__doCall( $name, $arguments );
			$time           = microtime( true ) - $start;
			$this->calls[ ] = (object)compact( 'start', 'time', 'name', 'arguments', 'result' );
		} else {
			$result = $this->__doCall( $name, $arguments );
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
	 * @param bool $enabled
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
	public function getEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param bool $debug
	 *
	 * @return $this
	 */
	public function setDebug( $debug )
	{
		$this->debug = $debug;

		return $this
	}

	/**
	 * return bool
	 */
	public function getDebug()
	{
		return $this->debug;
	}
	
	/**
	 * @param bool $initialized
	 *
	 * @return $this
	 */
	public function setInitialized( $initialized )
	{
		$this->initialized = $initialized;

		return $this
	}

	/**
	 * return bool
	 */
	public function getInitialized()
	{
		return $this->initialized;
	}
	
	/**
	 * @param ParameterBag $options
	 *
	 * @return $this
	 */
	public function setOptions( array $options )
	{
		$this->options = new ParameterBag( $options );

		return $this
	}

	/**
	 * return ParameterBag
	 */
	public function getOptions()
	{
		return $this->options;
	}
}
