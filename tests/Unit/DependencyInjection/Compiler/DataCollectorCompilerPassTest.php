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

use Cache\Adapter\Chain\CachePoolChain;
use Cache\Adapter\Common\PhpCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\DataCollector\CacheProxyInterface;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\CacheBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function testDecoratedChainKeepsMemberLevelTraces()
    {
        $container = new ContainerBuilder();
        $container->register('cache.data_collector', CacheDataCollector::class)->setPublic(true);
        $container->register('cache.pool.first', ArrayCachePool::class)
            ->addTag('cache.provider')
            ->setPublic(true);
        $container->register('cache.pool.second', ArrayCachePool::class)
            ->addTag('cache.provider')
            ->setPublic(true);
        $container->register('cache.pool.chain', CachePoolChain::class)
            ->setArguments([[
                new Reference('cache.pool.first'),
                new Reference('cache.pool.second'),
            ]])
            ->addTag('cache.provider')
            ->setPublic(true);

        (new DataCollectorCompilerPass())->process($container);
        $container->compile();

        $first = $container->get('cache.pool.first');
        $second = $container->get('cache.pool.second');
        self::assertInstanceOf(PhpCachePool::class, $first);
        self::assertInstanceOf(CacheProxyInterface::class, $first);
        self::assertInstanceOf(PhpCachePool::class, $second);
        self::assertInstanceOf(CacheProxyInterface::class, $second);
        self::assertTrue($second->save($second->getItem('key')->set('value')));
        $second->clearCalls();

        $chain = $container->get('cache.pool.chain');
        self::assertInstanceOf(PhpCachePool::class, $chain);
        self::assertSame('value', $chain->getItem('key')->get());

        $collector = $container->get('cache.data_collector');
        self::assertInstanceOf(CacheDataCollector::class, $collector);
        $collector->collect(new Request(), new Response());

        $firstCalls = $collector->getCalls()['cache.pool.first'];
        self::assertSame(['getItem', 'save'], array_column($firstCalls, 'name'));
        self::assertSame(1, $firstCalls[0]->misses);
        $secondCalls = $collector->getCalls()['cache.pool.second'];
        self::assertSame(['getItem'], array_column($secondCalls, 'name'));
        self::assertSame(1, $secondCalls[0]->hits);
    }
}
