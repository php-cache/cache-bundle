<?php

declare(strict_types=1);

namespace Cache\CacheBundle\DataCollector;

use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

final class TraceableTaggableCachePool extends TraceableCachePool implements TaggableCacheItemPoolInterface
{
    public function __construct(
        private readonly TaggableCacheItemPoolInterface $pool,
        string $name,
    ) {
        parent::__construct($pool, $name);
    }

    public function getItem(string $key): TaggableCacheItemInterface
    {
        return $this->traceGetItem($key, fn (): TaggableCacheItemInterface => $this->pool->getItem($key));
    }

    /**
     * @return iterable<string, TaggableCacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = $this->traceGetItems(
            $keys,
            fn () => $this->pool->getItems($keys),
            TaggableCacheItemInterface::class,
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
