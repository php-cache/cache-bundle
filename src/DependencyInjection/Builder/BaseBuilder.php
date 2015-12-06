<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Builder;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class BaseBuilder
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class BaseBuilder
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function __construct(ContainerBuilder $container)
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
