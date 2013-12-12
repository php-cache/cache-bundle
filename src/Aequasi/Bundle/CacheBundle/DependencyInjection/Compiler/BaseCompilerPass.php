<?php
/**
 * @author    Aaron Scherer
 * @date      12/6/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BaseCompilerPass
 *
 * @package Aequasi\Bundle\CacheBundle\DependencyInjection\Compiler
 */
abstract class BaseCompilerPass implements CompilerPassInterface
{

    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
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