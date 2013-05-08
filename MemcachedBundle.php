<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Aequasi\Bundle\MemcachedBundle\DependencyInjection\Compiler\EnableSessionSupport;

/**
 * MemcachedBundle Class
 */
class MemcachedBundle extends Bundle
{

	/**
	 * {@inheritDoc}
	 */
	public function build( ContainerBuilder $container )
	{
		parent::build( $container );

		$container->addCompilerPass( new EnableSessionSupport() );
		$container->addCompilerPass( new EnableKeyMapSupport() );
	}
}
