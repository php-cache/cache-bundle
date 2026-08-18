<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DependencyInjection\Compiler;

use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DataCollectorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('cache.data_collector')) {
            return;
        }

        $collector = $container->getDefinition('cache.data_collector');
        foreach (array_keys($container->findTaggedServiceIds('cache.provider')) as $id) {
            $innerId = $id.'.php_cache_inner';
            $inner = $container->getDefinition($id);
            $public = $inner->isPublic();
            $tags = $inner->getTags();

            foreach (array_keys($tags) as $tag) {
                $inner->clearTag($tag);
            }
            $inner->setPublic(false);
            $container->removeDefinition($id);
            $container->setDefinition($innerId, $inner);

            $decorator = $container->register($id, TraceableCachePool::class)
                ->setFactory([TraceableCachePool::class, 'create'])
                ->setArguments([new Reference($innerId), $id])
                ->setPublic($public);
            foreach ($tags as $tag => $attributes) {
                foreach ($attributes as $values) {
                    $decorator->addTag($tag, $values);
                }
            }

            $collector->addMethodCall('addInstance', [$id, new Reference($id)]);
        }
    }
}
