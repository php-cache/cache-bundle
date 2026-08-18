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

namespace Cache\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\CacheBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class DataCollectorCompilerPassTest extends TestCase
{
    public function testDecoratesTaggedPoolsWithoutInstantiatingThemDuringCompilation()
    {
        $container = new ContainerBuilder();
        $container->register('cache.data_collector', CacheDataCollector::class)->setPublic(true);
        $container->register('cache.pool', ArrayCachePool::class)
            ->addTag('cache.provider')
            ->setPublic(true);

        (new DataCollectorCompilerPass())->process($container);

        self::assertTrue($container->hasDefinition('cache.pool.php_cache_inner'));
        $definition = $container->getDefinition('cache.pool');
        self::assertSame(TraceableCachePool::class, $definition->getClass());
        self::assertEquals(new Reference('cache.pool.php_cache_inner'), $definition->getArgument(0));
        self::assertSame('cache.pool', $definition->getArgument(1));

        $container->compile();
        self::assertInstanceOf(TraceableCachePool::class, $container->get('cache.pool'));
        self::assertSame('cache.pool', $container->get('cache.pool')->getName());
    }

    public function testDoesNothingWithoutACollector()
    {
        $container = new ContainerBuilder();
        $container->register('cache.pool', ArrayCachePool::class)->addTag('cache.provider');

        (new DataCollectorCompilerPass())->process($container);

        self::assertSame(ArrayCachePool::class, $container->getDefinition('cache.pool')->getClass());
    }

    public function testDecoratedTaggablePoolsKeepTargetedInvalidation()
    {
        $container = new ContainerBuilder();
        $container->register('cache.data_collector', CacheDataCollector::class)->setPublic(true);
        $container->register('cache.pool', ArrayCachePool::class)
            ->addTag('cache.provider')
            ->setPublic(true);

        (new DataCollectorCompilerPass())->process($container);
        $container->compile();

        $pool = $container->get('cache.pool');
        self::assertInstanceOf(TaggableCacheItemPoolInterface::class, $pool);
        $router = $pool->getItem('router')->set('router');
        $session = $pool->getItem('session')->set('session');
        self::assertInstanceOf(TaggableCacheItemInterface::class, $router);
        self::assertInstanceOf(TaggableCacheItemInterface::class, $session);
        $pool->save($router->setTags(['router']));
        $pool->save($session->setTags(['session']));

        self::assertTrue($pool->invalidateTag('router'));
        self::assertFalse($pool->hasItem('router'));
        self::assertTrue($pool->hasItem('session'));
    }
}
