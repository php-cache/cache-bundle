<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 * Based on Lsw\MemcachedBundle by Christian Soronellas
 */
class AequasiCacheExtension extends Extension
{

	/**
	 * Loads the configs for Cache and puts data into the container
	 *
	 * @param array            $configs   Array of configs
	 * @param ContainerBuilder $container Container Object
	 */
	public function load( array $configs, ContainerBuilder $container )
	{
		$configuration = $this->getConfiguration( $configs, $container );
		$config        = $this->processConfiguration( $configuration, $configs );

		$loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );

		$loader->load( 'config.yml' );
		if ( $container->getParameter( 'kernel.debug' ) ) {
			$loader->load( 'debug.yml' );
		}

		if ( isset( $config[ 'session' ] ) ) {
			$this->enableSessionSupport( $config, $container );
		}
		if ( isset( $config[ 'doctrine' ] ) ) {
			$this->loadDoctrine( $config, $container );
		}
		if ( isset( $config[ 'instances' ] ) ) {
			$this->addInstances( $config, $container );
		}
	}

	/**
	 * @param array            $config
	 * @param ContainerBuilder $container
	 *
	 * @return Configuration
	 */
	public function getConfiguration( array $config, ContainerBuilder $container )
	{
		return new Configuration( $container->getParameter( 'kernel.debug' ) );
	}

	/**
	 * Loads the Doctrine configuration.
	 *
	 * @param array            $config    A configuration array
	 * @param ContainerBuilder $container A ContainerBuilder instance
	 */
	protected function loadDoctrine( array $config, ContainerBuilder $container )
	{
		$types = array( 'orm', 'odm' );
		foreach( $types as $managerType ) {
			foreach ( $config[ 'doctrine' ][ 'managerType' ] as $name => $ems ) {
				foreach( $ems as $em => $caches ) {
					foreach( $caches as $cacheType => $cache ) {
						// Get the type
						$type = strtolower( $cache[ 'type' ] );

						if( !isset( $cache[ 'instance' ] ) ) {
							throw new \Exception( 
								sprintf( "There was no instance passed. Please specify a instance in the %s entity manager under the %s type", $em, $cacheType )
							); 
						}
						$client = new Reference( sprintf( 'cache.%s.%s', $type, $cache[ 'instance' ] ) );
						$def    = new Definition( $container->getParameter( sprintf( 'cache.doctrine.%s.class', $type ) ) );
						$def->setScope( ContainerInterface::SCOPE_CONTAINER );
						$def->setCacheInstance( $client );
						if( !empty( $cache[ 'namespace' ]  ) ) {
							$def->addMethodCall( 'setNamespace', array( $cache[ 'namespace' ] ) );
						}
						$container->setDefinition( sprintf( 'doctrine.%s.%s_%s', $managerType, $em, $cacheType ), $def );
					}
				}
			}
		}
	}

	/**
	 * Enables session support for memcached
	 *
	 * @param array            $config    Configuration for bundle
	 * @param ContainerBuilder $container Service container
	 *
	 * @throws LogicException
	 */
	private function enableSessionSupport( array $config, ContainerBuilder $container )
	{
		$instance = $config[ 'session' ][ 'instance' ];
		$type     = $config[ 'session' ][ 'type' ];
		if ( null === $instance || null === $type ) {
			return;
		}
		if ( !isset( $config[ 'instances' ] ) || !isset( $config[ 'instances' ][ $type ][ $instance ] ) ) {
			throw new LogicException( sprintf( 'Failed to hook into the session. The instance "%s[%s]" doesn\'t exist!', $type, $instance ) );
		}
		// calculate options
		$sessionOptions = $container->getParameter( 'session.storage.options' );
		$options        = array();
		if ( isset( $config[ 'session' ][ 'ttl' ] ) ) {
			$options[ 'expiretime' ] = $config[ 'session' ][ 'ttl' ];
		} elseif ( isset( $sessionOptions[ 'cookie_lifetime' ] ) ) {
			$options[ 'expiretime' ] = $sessionOptions[ 'cookie_lifetime' ];
		}
		if ( isset( $config[ 'session' ][ 'prefix' ] ) ) {
			$options[ 'prefix' ] = $config[ 'session' ][ 'prefix' ];
		}
		// load the session handler
		$definition = new Definition( $container->getParameter( sprintf( 'cache.session.%s.class', $type ) );
		$container->setDefinition( sprintf( 'cache.%s.session_handler', $type ), $definition );
		$container->setAlias( 'cache.session_handler', sprintf( 'cache.%s.session_handler', $type ) );
		$definition
			->addArgument( new Reference( sprintf( 'cache.%s.%s', $type, $instance ) ) )
			->addArgument( $options );
		$this->addClassesToCompile( array( $definition->getClass() ) );
	}

	/**
	 * Adds cache instances to the service container
	 *
	 * @param array            $config    A configuration array
	 * @param ContainerBuilder $container Service container
	 *
	 * @throws LogicException
	 */
	private function addInstances( array $config, ContainerBuilder $container )
	{
		foreach ( $config[ 'instances' ] as $instance => $instanceConfig ) {
			$this->newCacheInstance( $instance, $instanceConfig, $container );
		}
	}

	private function checkTypeExtension( $type )
	{
		$type = strtolower( $type );
		switch( $type ) {
			case 'memcached':
			case 'memcache':
			case 'apc':
			case 'xcache':
				if( extension_loaded( $type ) ) 
					return true;
				throw new LogicException( sprintf( "%s extension is not loaded! To configure %s instances, it MUST be loaded!", ucwords( $type ), $type ) );
			case 'array':
			default:
				return true;
		}
	}

	/**
	 * Creates a new Cache definition
	 *
	 *
	 * Taken and modified from
	 * @link https://github.com/LeaseWeb/LswMemcacheBundle/blob/master/DependencyInjection/LswMemcacheExtension.php
	 *
	 * @param string           $name      Instance name
	 * @param array            $config    Instance configuration
	 * @param ContainerBuilder $container Service container
	 *
	 * @throws \LogicException
	 */
	private function newCacheInstance( $name, array $config, ContainerBuilder $container )
	{

		// Get the type and make sure the extension is there
		$type = strtolower( $config[ 'type' ] );
		$this->checkTypeExtension( $type );
	
		$class = isset( $config[ 'class' ] ) ? $config[ 'class' ] : sprintf( 'Aequasi\Bundle\CacheBundle\Driver\%sDriver', ucwords( $type ) );

		$implement = 'Aequasi\Bundle\CacheBundle\Driver\CacheDriverInterface';
		if( !self::instanceOf( $class, $implement ) ) {
			throw new LogicException( sprintf( "Class must implement '%s'. %s does not.", $implement, $class ) );
		}

		$instance = new Definition( $class );
		
		// Is this Instance Enabled
		$instance->addArgument( $config[ 'enabled' ] );

		// Is this Instance Logging
		$instance->addArgument( new Parameter( 'kernel.debug' ) );

		// Get default options
		$parameter = sprintf( 'cache.%s.default_options', $type );
		if( $container->hasParameter( $type ) ) {
			$options = $container->getParameter( $type );
			foreach( $options as $key => $value ) {
				if( !isset( $config[ 'options' ][ $key ] ) ) {
					$config[ 'options' ][ $key ] = $value;
				}
			}
		}
		
		// Pass in options
		$instance->addArgument( $config );

		// Check if Key Map logging is enabled
		$implement = 'Aequasi\Bundle\CacheBundle\Driver\KeyMapCacheDriverInterface';
		if( self::instanceOf( $class, $implement ) && $config[ 'keyMap' ][ 'enabled' ] ) {
			$instance->addMethodCall( 'setupKeyMap', array( $config[ 'keyMap' ], new Reference( 'doctrine' ) ) );
		}

		// Add the service to the container
		$serviceName = sprintf( 'cache.%s.%s', $type, $name );
		$container->setDefinition( $serviceName, $instance );

		// Add the service to the data collector
		if ( $container->hasDefinition( 'data_collector.cache' ) ) {
			$definition = $container->getDefinition( 'data_collector.cache' );
			$definition->addMethodCall( 'addInstance', array( $name, $options, new Reference( $serviceName ) ) );
		}
	}

	public static function instanceOf( $class, $interface )
	{
		return (bool) in_array( $interface, class_implements( $class ) );
	}
}
