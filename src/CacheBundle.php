<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle;

use Cache\CacheBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\CacheTaggingPass());
        $container->addCompilerPass(new Compiler\SessionSupportCompilerPass());
        $container->addCompilerPass(new Compiler\DoctrineCompilerPass());

        if ($container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new Compiler\DataCollectorCompilerPass());
        }
    }
}
