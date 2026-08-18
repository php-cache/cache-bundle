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

namespace Cache\CacheBundle\Tests\Unit\DataCollector;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\DataCollector\CacheProxyInterface;
use Cache\CacheBundle\DataCollector\TraceableAdapterEvent;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CacheDataCollectorTest extends TestCase
{
    public function testCollectsPerPoolAndTotalStatistics()
    {
        $pool = new TraceableCachePool(new ArrayCachePool(), 'cache.pool');
        $item = $pool->getItem('key')->set('value');
        $pool->save($item);
        $pool->getItem('key');
        iterator_to_array($pool->getItems(['key', 'missing']));
        $pool->hasItem('key');
        $pool->hasItem('missing');
        iterator_to_array($pool->getItems(['missing-one', 'missing-two']));
        $pool->deleteItem('key');

        $collector = new CacheDataCollector();
        $collector->addInstance('cache.pool', $pool);
        $collector->collect(new Request(), new Response());

        self::assertSame([
            'calls' => 8,
            'time' => $collector->getStatistics()['cache.pool']['time'],
            'reads' => 8,
            'writes' => 1,
            'deletes' => 1,
            'hits' => 3,
            'misses' => 5,
            'hit_read_ratio' => 37.5,
        ], $collector->getTotals());
        self::assertCount(8, $collector->getCalls()['cache.pool']);
        self::assertSame('php-cache', $collector->getName());
    }

    public function testResetClearsCollectedData()
    {
        $collector = new CacheDataCollector();
        $collector->collect(new Request(), new Response());
        $collector->reset();

        self::assertSame([], $collector->getCalls());
        self::assertSame([], $collector->getStatistics());
        self::assertNull($collector->getTotals()['hit_read_ratio']);
    }

    public function testResetPreventsCallsLeakingIntoTheNextRequest()
    {
        $pool = new TraceableCachePool(new ArrayCachePool(), 'cache.pool');
        $collector = new CacheDataCollector();
        $collector->addInstance('cache.pool', $pool);

        $pool->getItem('request-one');
        $collector->collect(new Request(), new Response());
        self::assertCount(1, $collector->getCalls()['cache.pool']);

        $collector->reset();
        self::assertSame([], $pool->getCalls());

        $pool->hasItem('request-two');
        $collector->collect(new Request(), new Response());

        $secondRequestCalls = $collector->getCalls()['cache.pool'];
        self::assertCount(1, $secondRequestCalls);
        self::assertSame('hasItem', $secondRequestCalls[0]->name);
    }

    public function testCountsBulkDeletesWithoutInventingAReadRatio()
    {
        $event = new TraceableAdapterEvent('deleteItems', ['one', 'two'], 1.0);
        $event->end = 2.0;
        $event->result = true;
        $pool = $this->createMock(CacheProxyInterface::class);
        $pool->method('getCalls')->willReturn([$event]);
        $collector = new CacheDataCollector();
        $collector->addInstance('cache.pool', $pool);

        $collector->collect(new Request(), new Response());

        self::assertSame(2, $collector->getTotals()['deletes']);
        self::assertNull($collector->getStatistics()['cache.pool']['hit_read_ratio']);
    }
}
