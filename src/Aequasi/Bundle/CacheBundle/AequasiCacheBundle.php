<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\CacheBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler\EnableSessionSupport;

/**
 * CacheBundle Class
 */
class AequasiCacheBundle extends Bundle
{

	/**
	 * {@inheritDoc}
	 */
	public function build( ContainerBuilder $container )
	{
		parent::build( $container );

		$container->addCompilerPass( new EnableSessionSupport() );
	}
}
