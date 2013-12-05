<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * EnableSessionSupport is a compiler pass to set the session handler.
 * Based on Emagister\CacheBundle by Christian Soronellas
 */
class EnableSessionSupport implements CompilerPassInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function process( ContainerBuilder $container )
	{
		// If there is no active session support, return
		if( !$container->hasAlias( 'session.storage' ) ) {
			return;
		}

		// If the aequasi.cache.session_handler service is loaded set the alias
		if ( $container->hasDefinition( 'aequasi.cache.session_handler' ) ) {
			$container->setAlias( 'session.handler', 'aequasi.cache.session_handler' );
		}
	}
}
