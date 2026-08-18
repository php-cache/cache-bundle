<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit\DataCollector;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\Namespaced\NamespacedCachePool;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class TraceableCachePoolTest extends TestCase
{
    public function testFactoryKeepsNonTaggablePoolsNonTaggable()
    {
        $pool = TraceableCachePool::create($this->createMock(CacheItemPoolInterface::class), 'cache.pool');

        self::assertSame(TraceableCachePool::class, $pool::class);
    }

    public function testRecordsPoolOperationsAndHitCounts()
    {
        $pool = new TraceableCachePool(new ArrayCachePool(), 'cache.pool');
        $item = $pool->getItem('key')->set('value');

        self::assertTrue($pool->save($item));
        self::assertTrue($pool->hasItem('key'));
        self::assertSame('value', $pool->getItem('key')->get());
        self::assertCount(2, iterator_to_array($pool->getItems(['key', 'missing'])));
        self::assertTrue($pool->deleteItem('key'));

        $calls = $pool->getCalls();
        self::assertSame(['getItem', 'save', 'hasItem', 'getItem', 'getItems', 'deleteItem'], array_column($calls, 'name'));
        self::assertSame(1, $calls[3]->hits);
        self::assertSame(1, $calls[4]->hits);
        self::assertSame(1, $calls[4]->misses);
        self::assertSame('cache.pool', $pool->getName());
    }

    public function testDelegatesDeferredAndBulkOperations()
    {
        $pool = new TraceableCachePool(new ArrayCachePool(), 'cache.pool');
        $item = $pool->getItem('one')->set('value');

        self::assertTrue($pool->saveDeferred($item));
        self::assertTrue($pool->commit());
        self::assertTrue($pool->deleteItems(['one']));
        self::assertTrue($pool->clear());
        self::assertSame(
            ['getItem', 'saveDeferred', 'commit', 'deleteItems', 'clear'],
            array_column($pool->getCalls(), 'name'),
        );
    }

    public function testGetItemsPreservesNumericStringKeys()
    {
        $pool = new TraceableCachePool(new ArrayCachePool(), 'cache.pool');

        $keys = [];
        foreach ($pool->getItems(['123']) as $key => $item) {
            $keys[] = $key;
            self::assertSame('123', $item->getKey());
        }

        self::assertSame(['123'], $keys);
    }

    public function testRecordsPublicKeysFromNamespacedPools()
    {
        $pool = new TraceableCachePool(
            new NamespacedCachePool(new ArrayCachePool(), 'namespace'),
            'cache.pool',
        );
        $item = $pool->getItem('key')->set('value');
        self::assertTrue($pool->save($item));

        $calls = $pool->getCalls();
        self::assertInstanceOf(CacheItemInterface::class, $calls[0]->result);
        self::assertSame('key', $calls[0]->result->getKey());
        self::assertInstanceOf(CacheItemInterface::class, $calls[1]->argument);
        self::assertSame('key', $calls[1]->argument->getKey());
    }

    /**
     * @param \Closure(TraceableCachePool, CacheItemInterface): mixed $operation
     */
    #[DataProvider('failingOperations')]
    public function testFinalizesTheTraceAndRethrowsPoolExceptions(string $method, \Closure $operation)
    {
        $inner = $this->createMock(CacheItemPoolInterface::class);
        $exception = new \RuntimeException('pool failed');
        $inner->method($method)->willThrowException($exception);
        $pool = new TraceableCachePool($inner, 'cache.pool');

        try {
            $operation($pool, $this->createMock(CacheItemInterface::class));
            self::fail('The pool exception was not rethrown.');
        } catch (\RuntimeException $caught) {
            self::assertSame($exception, $caught);
        }

        $calls = $pool->getCalls();
        self::assertCount(1, $calls);
        self::assertSame($exception, $calls[0]->result);
        self::assertGreaterThanOrEqual($calls[0]->start, $calls[0]->end);

        $collector = new CacheDataCollector();
        $collector->addInstance('cache.pool', $pool);
        $collector->collect(new \Symfony\Component\HttpFoundation\Request(), new \Symfony\Component\HttpFoundation\Response());

        self::assertSame(1, $collector->getTotals()['calls']);
    }

    public function testTracesTagInvalidationOperations()
    {
        $inner = new ArrayCachePool();
        $pool = TraceableCachePool::create($inner, 'cache.pool');
        self::assertInstanceOf(TaggableCacheItemPoolInterface::class, $pool);

        self::assertTrue($pool->invalidateTag('router'));
        self::assertTrue($pool->invalidateTags(['session', 'router']));

        $calls = $pool->getCalls();
        self::assertSame(['invalidateTag', 'invalidateTags'], array_column($calls, 'name'));
        self::assertSame([true, true], array_column($calls, 'result'));
    }

    public function testTaggableTraceReturnsAndRecordsTaggableItems()
    {
        $pool = TraceableCachePool::create(new ArrayCachePool(), 'cache.pool');
        self::assertInstanceOf(TaggableCacheItemPoolInterface::class, $pool);

        self::assertInstanceOf(TaggableCacheItemInterface::class, $pool->getItem('one'));
        $keys = [];
        foreach ($pool->getItems(['one', '123']) as $key => $item) {
            $keys[] = $key;
            self::assertInstanceOf(TaggableCacheItemInterface::class, $item);
        }

        self::assertSame(['one', '123'], $keys);
        self::assertSame(['getItem', 'getItems'], array_column($pool->getCalls(), 'name'));
    }

    public function testTaggableTraceRejectsNonTaggableBulkItems()
    {
        $inner = $this->createMock(TaggableCacheItemPoolInterface::class);
        $inner->method('getItems')->willReturn([$this->createMock(CacheItemInterface::class)]);
        $pool = TraceableCachePool::create($inner, 'cache.pool');
        self::assertInstanceOf(TaggableCacheItemPoolInterface::class, $pool);

        $this->expectException(\UnexpectedValueException::class);
        iterator_to_array($pool->getItems(['key']));
    }

    /**
     * @return iterable<string, array{string, \Closure(TraceableCachePool, CacheItemInterface): mixed}>
     */
    public static function failingOperations(): iterable
    {
        yield 'getItem' => ['getItem', static fn (TraceableCachePool $pool, CacheItemInterface $item): CacheItemInterface => $pool->getItem('key')];
        yield 'getItems' => ['getItems', static fn (TraceableCachePool $pool, CacheItemInterface $item): array => iterator_to_array($pool->getItems(['key']))];
        yield 'hasItem' => ['hasItem', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->hasItem('key')];
        yield 'clear' => ['clear', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->clear()];
        yield 'deleteItem' => ['deleteItem', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->deleteItem('key')];
        yield 'deleteItems' => ['deleteItems', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->deleteItems(['key'])];
        yield 'save' => ['save', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->save($item)];
        yield 'saveDeferred' => ['saveDeferred', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->saveDeferred($item)];
        yield 'commit' => ['commit', static fn (TraceableCachePool $pool, CacheItemInterface $item): bool => $pool->commit()];
    }
}
