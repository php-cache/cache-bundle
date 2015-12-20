<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BaseCompilerPass.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class BaseCompilerPass implements CompilerPassInterface
{
    /**
     * @type ContainerBuilder
     */
    protected $container;

    /**
     * {@inheritdoc}
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
        return 'cache';
    }

    /**
     * @return mixed
     */
    abstract protected function prepare();
}
