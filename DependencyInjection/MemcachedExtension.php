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

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
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

		$this->setParameters( $container, $config );
		$container->setParameter( 'memcached', $config );

		$loader = new Loader\YamlFileLoader(
			$container,
			new FileLocator( __DIR__ . '/../Resources/config' )
		);
		$loader->load( 'services.yml' );

		$this->setupKeyMapping( $config[ 'keyMap' ], $container );
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
	 * @param ContainerBuilder $container
	 * @param array            $configs
	 */
	private function setParameters( ContainerBuilder $container, array $configs )
	{
		foreach ( $configs as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->setParameters( $container, $configs[ $key ], ltrim( 'memcached.' . $key, '.' ) );
				$container->setParameter( ltrim( 'memcached.' . $key, '.' ), $value );
			} else {
				$container->setParameter( ltrim( 'memcached.' . $key, '.' ), $value );
			}
		}
	}

	/**
	 * Sets up Key Mapping, if enabled
	 *
	 * Creates the necessary tables, if they arent there, and updates the service
	 *
	 * @param array            $configs
	 * @param ContainerBuilder $container
	 *
	 * @throws \Exception
	 */
	private function setupKeyMapping( array $configs, ContainerBuilder $container )
	{
		if( $configs[ 'enabled' ] ) {

			// Make sure the connection isn't empty
			if( $configs[ 'connection' ] === '' ) {
				throw new \Exception( "Please specify a `connection` for the keyMap setting under memcached. " );
			}

			// Grab the connection. Will throw a service not found if there isnt a connection with that name
			/** @var \Doctrine\DBAL\Connection $connection */
			$connection = $container->get( sprintf( 'doctrine.dbal.%s_connection', $configs[ 'connection' ] ) );

			// Create the table if it doesn't exist
			$sql = <<<SQL
CREATE IF NOT EXISTS TABLE `memcache_key_map` (
  `id` BIGINT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cache_key` VARCHAR(255) NOT NULL,
  `memory_size` BIGINT(32) UNSIGNED,
  `lifeTime` INT(11) UNSIGNED NOT NULL,
  `expiration` DATETIME NOT NULL,
  `insert_date` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`cache_key`),
  INDEX (`expiration`),
  INDEX (`insert_date`)
) ENGINE=INNODB;
SQL;
			$connection->executeQuery( $sql );


			// Fetch the memcached service, set key mapping to enabled, and set the connection
			$this->get( 'memcached' )
				->setKeyMapEnabled( true )
				->setKeyMapConnection( $connection );
		}
	}
}
