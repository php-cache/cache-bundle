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

namespace Cache\CacheBundle\DataCollector;

use Cache\Adapter\Common\PhpCacheItem;
use Cache\Adapter\Common\PhpCachePool;

final class TraceablePhpCachePool extends TraceableCachePool implements PhpCachePool
{
    public function __construct(
        private readonly PhpCachePool $pool,
        string $name,
    ) {
        parent::__construct($pool, $name);
    }

    public function getItem(string $key): PhpCacheItem
    {
        return $this->traceGetItem($key, fn (): PhpCacheItem => $this->pool->getItem($key));
    }

    /**
     * @return iterable<string, PhpCacheItem>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = $this->traceGetItems(
            $keys,
            fn () => $this->pool->getItems($keys),
            PhpCacheItem::class,
        );

        return $this->generateItems($items);
    }

    public function invalidateTag(string $tag): bool
    {
        return $this->trace('invalidateTag', $tag, fn (TraceableAdapterEvent $event): bool => $this->pool->invalidateTag($tag));
    }

    public function invalidateTags(array $tags): bool
    {
        return $this->trace('invalidateTags', $tags, fn (TraceableAdapterEvent $event): bool => $this->pool->invalidateTags($tags));
    }
}
