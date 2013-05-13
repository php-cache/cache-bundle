<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\DependencyInjection;

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
class MemcachedExtension extends Extension
{

	/**
	 * Loads the configs for Memcached and puts data into the container
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
		if ( isset( $config[ 'clusters' ] ) ) {
			$this->addClusters( $config, $container );
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
		foreach ( $config[ 'doctrine' ] as $name => $cache ) {
			$client = new Reference( sprintf( 'memcached.%s', $cache[ 'cluster' ] ) );
			foreach ( $cache[ 'entity_managers' ] as $em ) {
				$definition = new Definition( $container->getParameter( 'memcached.doctrine_cache.class' ) );
				$definition->setScope( ContainerInterface::SCOPE_CONTAINER );
				$definition->addMethodCall( 'setMemcached', array( $client ) );
				if ( $cache[ 'prefix' ] ) {
					$definition->addMethodCall( 'setPrefix', array( $cache[ 'prefix' ] ) );
				}
				$container->setDefinition( sprintf( 'doctrine.orm.%s_%s', $em, $name ), $definition );
			}
			foreach ( $cache[ 'document_managers' ] as $dm ) {
				$definition = new Definition( $container->getParameter( 'memcached.doctrine_cache.class' ) );
				$definition->setScope( ContainerInterface::SCOPE_CONTAINER );
				$definition->addMethodCall( 'setMemcached', array( $client ) );
				if ( $cache[ 'prefix' ] ) {
					$definition->addMethodCall( 'setPrefix', array( $cache[ 'prefix' ] ) );
				}
				$container->setDefinition( sprintf( 'doctrine.odm.mongodb.%s_%s', $dm, $name ), $definition );
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
		$cluster = $config[ 'session' ][ 'cluster' ];
		if ( null === $cluster ) {
			return;
		}
		if ( !isset( $config[ 'clusters' ] ) || !isset( $config[ 'clusters' ][ $cluster ] ) ) {
			throw new LogicException( sprintf( 'Failed to hook into the session. The cluster "%s" doesn\'t exist!', $cluster ) );
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
		$definition = new Definition( $container->getParameter( 'memcached.session_handler.class' ) );
		$container->setDefinition( 'memcached.session_handler', $definition );
		$definition
			->addArgument( new Reference( sprintf( 'memcached.%s', $cluster ) ) )
			->addArgument( $options );
		$this->addClassesToCompile( array( $definition->getClass() ) );
	}

	/**
	 * Adds memcached clusters to the service container
	 *
	 * @param array            $config    A configuration array
	 * @param ContainerBuilder $container Service container
	 *
	 * @throws LogicException
	 */
	private function addClusters( array $config, ContainerBuilder $container )
	{
		foreach ( $config[ 'clusters' ] as $cluster => $memcachedConfig ) {
			$this->newMemcachedClient( $cluster, $memcachedConfig, $container );
		}
	}

	/**
	 * Creates a new Memcached definition
	 *
	 *
	 * Taken and modified from
	 * @link https://github.com/LeaseWeb/LswMemcacheBundle/blob/master/DependencyInjection/LswMemcacheExtension.php
	 *
	 * @param string           $name      Cluster name
	 * @param array            $config    Cluster configuration
	 * @param ContainerBuilder $container Service container
	 *
	 * @throws \LogicException
	 */
	private function newMemcachedClient( $name, array $config, ContainerBuilder $container )
	{
		// Check if the Memcached extension is loaded
		if ( !extension_loaded( 'memcached' ) ) {
			throw LogicException( 'Memcached extension is not loaded! To configure memcached clients it MUST be loaded!' );
		}

		$memcached = new Definition( 'Aequasi\Bundle\MemcachedBundle\Cache\AntiStampedeMemcached' );
		$memcached->addArgument( new Parameter( 'kernel.debug' ) );

		// Check if it has to be persistent
		if ( isset( $config[ 'persistent_id' ] ) ) {
			$memcached->addArgument( $config[ 'persistent_id' ] );
		}

		// Check if Key Map logging is enabled
		if( $config[ 'keyMap' ][ 'enabled' ] ) {
			$memcached->addMethodCall( 'setupKeyMap', array( $config[ 'keyMap' ], new Reference( 'doctrine' ) ) );
		}

		// Add servers to the memcached client
		$servers = array();
		foreach ( $config[ 'hosts' ] as $host ) {
			$servers[ ] = array(
				$host[ 'host' ],
				$host[ 'port' ],
				$host[ 'weight' ]
			);
		}
		$memcached->addMethodCall( 'addServers', array( $servers ) );

		// Get default memcached options
		$options = $container->getParameter( 'memcached.default_options' );

		// Add overriden options
		if ( isset( $config[ 'options' ] ) ) {
			foreach ( $options as $key => $value ) {
				if ( isset( $config[ 'options' ][ $key ] ) ) {
					if ( $key == 'serializer' ) {
						// serializer option needs to be supported and is a constant
						if ( $value != 'php' && !constant( 'Memcached::HAVE_' . strtoupper( $value ) ) ) {
							throw new \LogicException( "Invalid serializer specified for Memcached: $value" );
						}
						$newValue = constant( 'Memcached::SERIALIZER_' . strtoupper( $value ) );
					} elseif ( $key == 'distribution' ) {
						// distribution is defined as a constant
						$newValue = constant( 'Memcached::DISTRIBUTION_' . strtoupper( $value ) );
					} else {
						$newValue = $config[ 'options' ][ $key ];
					}
					if ( $config[ 'options' ][ $key ] != $value ) {
						// not default, add method call and update options
						$constant = 'Memcached::OPT_' . strtoupper( $key );
						$memcached->addMethodCall( 'setOption', array( constant( $constant ), $newValue ) );
						$options[ $key ] = $newValue;
					}
				}
			}
		}

		// Make sure that config values are human readable
		foreach ( $options as $key => $value ) {
			$options[ $key ] = var_export( $value, true );
		}

		// Add the service to the container
		$serviceName = sprintf( 'memcached.%s', $name );
		$container->setDefinition( $serviceName, $memcached );
		// Add the service to the data collector
		if ( $container->hasDefinition( 'memcached.data_collector' ) ) {
			$definition = $container->getDefinition( 'memcached.data_collector' );
			$definition->addMethodCall( 'addClient', array( $name, $options, new Reference( $serviceName ) ) );
		}
	}
}
