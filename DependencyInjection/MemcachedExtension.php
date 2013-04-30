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
		$loader = new Loader\YamlFileLoader(
			$container,
			new FileLocator( __DIR__ . '/../Resources/config' )
		);
		$loader->load( 'services.yml' );

		$configuration = $this->getConfiguration( $configs, $container );
		$config        = $this->processConfiguration( $configuration, $configs );

		$this->setParameters( $container, $config );
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
}
