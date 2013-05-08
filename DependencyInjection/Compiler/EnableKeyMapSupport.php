<?php

namespace Aequasi\Bundle\MemcachedBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * EnableKeyMapSupport is a compiler pass to set the session handler.
 */
class EnableKeyMapSupport implements CompilerPassInterface
{

	/**
	 * {@inheritDoc}
	 */
	public function process( ContainerBuilder $container )
	{
		// If the memcached.key_map_handler service is loaded set the alias
		if ( $container->hasDefinition( 'memcached.key_map_support' ) ) {
			$container->setAlias( 'doctrine', $container->getDefinition( 'doctrine' ) );
		}
	}
}