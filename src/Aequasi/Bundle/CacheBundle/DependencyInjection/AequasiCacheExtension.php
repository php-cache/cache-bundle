<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date      2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 * Based on Lsw\CacheBundle by Christian Soronellas
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
		$configuration = new Configuration( $container->getParameter( 'kernel.debug' ) );
		$config        = $this->processConfiguration( $configuration, $configs );

		$loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__ . '/../Resources/config' ) );
		$loader->load( 'services.yml' );

		$container->setParameter( $this->getAlias() . '.instance', $config[ 'instances' ] );
		$container->setParameter( $this->getAlias() . '.session', $config[ 'session' ] );
		$container->setParameter( $this->getAlias() . '.doctrine', $config[ 'doctrine' ] );
	}
}
