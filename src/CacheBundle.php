<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Cache\CacheBundle\DependencyInjection\Compiler;

/**
 * Class AequasiCacheBundle
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\SessionSupportCompilerPass());
        $container->addCompilerPass(new Compiler\DoctrineSupportCompilerPass());

        if ($container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new Compiler\DataCollectorCompilerPass());
        }
    }
}
