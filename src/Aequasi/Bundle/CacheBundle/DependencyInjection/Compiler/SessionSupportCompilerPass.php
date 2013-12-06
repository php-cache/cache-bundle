<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * SessionSupportCompilerPass is a compiler pass to set the session handler.
 */
class SessionSupportCompilerPass extends BaseCompilerPass
{

	protected function prepare()
	{
		// If there is no active session support, return
		if( !$this->container->hasAlias( 'session.storage' ) ) {
			return;
		}

		// If the aequasi.cache.session_handler service is loaded set the alias
		if ( $this->container->hasParameter( 'aequasi_cache.session' ) ) {
			$this->enableSessionSupport( $this->container->getParameter( $this->getAlias() . '.session' ) );
		}
	}

	/**
	 * Enables session support for memcached
	 *
	 * @param array            $config    Configuration for bundle
	 *
	 * @throws InvalidConfigurationException
	 */
	private function enableSessionSupport( array $config )
	{
		$instance = $config[ 'session' ][ 'instance' ];
		$instances = $this->container->get( $this->getAlias() . '.instance' );

		if( null === $instance ) {
			return;
		}
		if( !isset( $instances[ $instance ] ) ) {
			throw new InvalidConfigurationException( sprintf( 'Failed to hook into the session. The instance "%s" doesn\'t exist!', $instance ) );
		}

		if( in_array(  $instances[ $instance ][ 'type' ], array( 'memcache', 'redis', 'memcached' ) ) )
		{
			throw new InvalidConfigurationException( sprintf( "%s is not a valid cache type for session support. Please use Memcache, Memcached, or Redis. ", $instance ) );
		}


		// calculate options
		$sessionOptions = $this->container->getParameter( 'session.storage.options' );
		if( isset( $sessionOptions[ 'cookie_lifetime' ] ) && !isset( $config[ 'session' ][ 'cookie_lifetime' ] ) ) {
			$config[ 'session' ][ 'cookie_lifetime' ] = $sessionOptions[ 'cookie_lifetime' ];
		}
		// load the session handler
		$definition = new Definition( $this->container->getParameter( sprintf( '%s.session.handler.class', $this->getAlias() ) ) );
		$this->container->setDefinition( sprintf( '%s.session_handler', $this->getAlias() ), $definition );
		$this->container->setAlias( 'cache.session_handler', sprintf( '%s.session_handler', $this->getAlias() ) );
		$definition->addArgument( new Reference( sprintf( '%s.instance.%s', $this->getAlias(), $instance ) ) )
		           ->addArgument( $config[ 'session' ] );

		$this->container->setAlias( 'session.handler', $this->getAlias() .'.session_handler' );
	}
}
