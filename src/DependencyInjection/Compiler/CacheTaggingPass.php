<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Make sure to tag all cache services we can find.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheTaggingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // get service ids form parameters
        $serviceIds = $container->getParameter('cache.provider_service_ids');

        foreach ($serviceIds as $id) {
            $def = $container->findDefinition($id);
            if (!$def->hasTag('cache.provider')) {
                $def->addTag('cache.provider');
            }
        }
    }
}
