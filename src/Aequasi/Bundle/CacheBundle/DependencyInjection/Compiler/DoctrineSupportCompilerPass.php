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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DoctrineCompilerPass is a compiler pass to set the doctrine caches.
 */
class DoctrineSupportCompilerPass extends BaseCompilerPass
{

	protected function prepare()
	{
		// If there is no active session support, return
		if( !$this->container->hasAlias( 'doctrine' ) ) {
			return;
		}

		// If the aequasi.cache.session_handler service is loaded set the alias
		if ( $this->container->hasParameter( 'aequasi_cache.doctrine' ) ) {
			$this->enableDoctrineSupport( $this->container->getParameter( $this->getAlias() . '.doctrine' ) );
		}
	}

	/**
	 * Loads the Doctrine configuration.
	 *
	 * @param array            $config    A configuration array
	 *
	 * @throws InvalidConfigurationException
	 */
	protected function enableDoctrineSupport( array $config )
	{
		$types = array( 'orm', 'odm' );
		foreach( $types as $managerType ) {
			foreach( $config[ 'doctrine' ][ 'managerType' ] as $name => $ems ) {
				foreach( $ems as $em => $caches ) {
					foreach( $caches as $cacheType => $cache ) {
						// Get the type
						$type = strtolower( $cache[ 'type' ] );

						if( !isset( $cache[ 'instance' ] ) ) {
							throw new InvalidConfigurationException( sprintf( "There was no instance passed. Please specify a instance in the %s entity manager under the %s type", $em, $cacheType ) );
						}
						$client = new Reference( sprintf( '%s.instance.%s', $this->getAlias(), $cache[ 'instance' ] ) );
						$def    = new Definition( $this->container->getParameter( sprintf( 'cache.doctrine.%s.class', $type ) ) );
						$def->setScope( ContainerInterface::SCOPE_CONTAINER );
						$def->setCacheInstance( $client );
						if( !empty( $cache[ 'namespace' ] ) ) {
							$def->addMethodCall( 'setNamespace', array( $cache[ 'namespace' ] ) );
						}
						$this->container->setDefinition( sprintf( 'doctrine.%s.%s_%s', $managerType, $em, $cacheType ), $def );
					}
				}
			}
		}
	}
}