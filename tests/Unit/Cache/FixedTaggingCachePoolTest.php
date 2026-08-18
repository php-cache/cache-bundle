<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit\Cache;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\Cache\FixedTaggingCachePool;
use Cache\TagInterop\TaggableCacheItemInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

final class FixedTaggingCachePoolTest extends TestCase
{
    public function testReturnsTaggableItems(): void
    {
        $pool = new FixedTaggingCachePool(new ArrayCachePool(), ['tag']);

        self::assertInstanceOf(TaggableCacheItemInterface::class, $pool->getItem('one'));
        $keys = [];
        foreach ($pool->getItems(['one', '123']) as $key => $item) {
            $keys[] = $key;
            self::assertInstanceOf(TaggableCacheItemInterface::class, $item);
        }
        self::assertSame(['one', '123'], $keys);
    }

    public function testSavedItemsReceiveTheFixedTags(): void
    {
        $inner = new ArrayCachePool();
        $pool = new FixedTaggingCachePool($inner, ['session']);
        $item = $pool->getItem('key')->set('value');

        self::assertTrue($pool->save($item));
        self::assertTrue($pool->getItem('key')->isHit());
        self::assertTrue($pool->invalidateTag('session'));
        self::assertFalse($pool->getItem('key')->isHit());
    }

    public function testDeferredItemsReceiveTheFixedTags(): void
    {
        $inner = new ArrayCachePool();
        $pool = new FixedTaggingCachePool($inner, ['router']);
        $item = $pool->getItem('key')->set('value');

        self::assertTrue($pool->saveDeferred($item));
        self::assertTrue($pool->commit());
        self::assertTrue($pool->invalidateTags(['router']));
        self::assertFalse($pool->getItem('key')->isHit());
    }

    public function testRejectsItemsFromNonTaggablePools(): void
    {
        $pool = new FixedTaggingCachePool(new ArrayCachePool(), ['tag']);

        $this->expectException(\InvalidArgumentException::class);
        $pool->save($this->createMock(CacheItemInterface::class));
    }

    public function testDelegatesRegularPoolOperations(): void
    {
        $inner = new ArrayCachePool();
        $pool = new FixedTaggingCachePool($inner, ['tag']);
        $pool->save($pool->getItem('one')->set('value'));
        $pool->save($pool->getItem('two')->set('value'));

        self::assertTrue($pool->hasItem('one'));
        self::assertCount(2, iterator_to_array($pool->getItems(['one', 'two'])));
        self::assertTrue($pool->deleteItem('one'));
        self::assertTrue($pool->deleteItems(['two']));
        self::assertTrue($pool->clear());
    }
}
