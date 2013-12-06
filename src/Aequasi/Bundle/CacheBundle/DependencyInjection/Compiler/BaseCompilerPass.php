<?php
/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @copyright Underground Elephant
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

abstract class BaseCompilerPass implements CompilerPassInterface
{

	/**
	 * @var ContainerBuilder
	 */
	protected $container;

	/**
	 * {@inheritDoc}
	 */
	public function process( ContainerBuilder $container )
	{
		$this->container = $container;

		$this->prepare();
	}

	/**
	 * @return string
	 */
	protected function getAlias()
	{
		return 'aequasi_cache';
	}

	/**
	 * @return mixed
	 */
	abstract protected function prepare();
} 