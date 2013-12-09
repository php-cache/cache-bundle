<?php
/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @copyright Underground Elephant
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class ServiceBuilderCompilerPass extends BaseCompilerPass
{

	/**
	 * {@inheritDoc}
	 */
	protected function prepare( )
	{
		$instances = $this->container->getParameter( $this->getAlias() . '.instance' );

		foreach( $instances as $name => $instance ) {
			$this->buildInstance( $name, $instance );
		}
	}

	private function buildInstance( $name, array $instance )
	{
		$typeId = $this->getAlias() . '.' . $instance[ 'type' ];
		if( !$this->container->hasDefinition( $typeId ) ) {
			throw new InvalidConfigurationException( sprintf( "`%s` is not a valid cache type. If you are using a custom type, make sure to add your service. ", $instance[ 'type' ] ) );
		}

		$service = $this->buildService( $typeId, $name, $instance );

		$this->prepareCacheClass( $service, $name, $instance );
	}

	/**
	 * @param       $typeId
	 * @param       $name
	 * @param array $instance
	 *
	 * @return Definition
	 */
	private function buildService( $typeId, $name, array $instance )
	{
		$namespace = is_null( $instance[ 'namespace' ] ) ? $name : $instance[ 'namespace' ];

		$service = $this->container->setDefinition( $this->getAlias() . '.instance.' . $name, new DefinitionDecorator( $typeId ) )
		                           ->addMethodCall( 'setNamespace', array( $namespace ) );

		return $service;
	}

	private function prepareCacheClass( Definition $service, $name, array $instance )
	{
		$type = $instance[ 'type' ];
		$id = sprintf( "%s.instance.%s.%s_instance", $this->getAlias(), $name, $type );
		$cache = null;

		switch( $type ) {
			case 'memcache':
				if( empty( $instance[ 'id' ] ) ) {
					$cache = new Definition( 'Memcache' );
					$cache->setPublic( false );
					foreach( $instance[ 'hosts' ] as $config ) {
						$host    = empty( $config[ 'host' ] ) ? 'localhost' : $config[ 'host' ];
						$port    = empty( $config[ 'port' ] ) ? 11211 : $config[ 'port' ];
						$timeout = is_null( $config[ 'timeout' ] ) ? 0 : $config[ 'timeout' ];
						$cache->addMethodCall( 'addServer', $host, $port, $timeout );
					}
					unset( $config );

					$this->container->setDefinition( $id, $cache );

				} else {
					$id = $instance[ 'id' ];
				}
				$service->addMethodCall( sprintf( 'set%s', ucwords( $type ) ), array( new Reference( $id ) ) );
				break;
			case 'memcached':
				if( empty( $instance[ 'id' ] ) ) {
					$cache = new Definition( 'Memcached' );
					if( $instance[ 'persistent' ] ) {
						$cache->setArguments( array( serialize( $instance[ 'hosts' ] ) ) );
					}

					$cache->setPublic( false );
					foreach( $instance[ 'hosts' ] as $config ) {
						$host   = is_null( $config[ 'host' ] ) ? 'localhost' : $config[ 'host' ];
						$port   = is_null( $config[ 'port' ] ) ? 11211 : $config[ 'port' ];
						$weight = is_null( $config[ 'weight' ] ) ? 0 : $config[ 'weight' ];
						$cache->addMethodCall( 'addServer', array( $host, $port, $weight ) );
					}
					unset( $config );

					$this->container->setDefinition( $id, $cache );
				} else {
					$id = $instance[ 'id' ];
				}
				$service->addMethodCall( sprintf( 'set%s', ucwords( $type ) ), array( new Reference( $id ) ) );
				break;
			case 'redis':
				if( empty( $instance[ 'id' ] ) ) {
					$cache = new Definition( 'Redis' );
					$cache->setPublic( false );
					foreach( $instance[ 'hosts' ] as $config ) {
						$host    = empty( $config[ 'host' ] ) ? 'localhost' : $config[ 'host' ];
						$port    = empty( $config[ 'port' ] ) ? 6379 : $config[ 'port' ];
						$timeout = is_null( $config[ 'timeout' ] ) ? 2 : $config[ 'timeout' ];
						$cache->addMethodCall( 'connect', $host, $port, $timeout );
					}
					unset( $config );

					$this->container->setDefinition( $id, $cache );
				} else {
					$id = $instance[ 'id' ];
				}
				$service->addMethodCall( sprintf( 'set%s', ucwords( $type ) ), array( new Reference( $id ) ) );
				break;
			case 'file_system':
			case 'php_file':
				$directory = is_null( $instance[ 'directory' ] ) ? '%kernel.cache_dir%/doctrine/cache' : $instance[ 'directory' ];
				$extension = is_null( $instance[ 'extension' ] ) ? null : $instance[ 'extension' ];

				$service->setArguments( array( $directory, $extension ) );
				break;
		}
	}
} 