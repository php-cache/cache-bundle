<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Cache;

use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

final class FixedTaggingCachePool implements TaggableCacheItemPoolInterface
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        private readonly TaggableCacheItemPoolInterface $cache,
        private readonly array $tags,
    ) {
    }

    public function getItem(string $key): TaggableCacheItemInterface
    {
        return $this->cache->getItem($key);
    }

    /**
     * @return iterable<string, TaggableCacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        return $this->cache->getItems($keys);
    }

    public function hasItem(string $key): bool
    {
        return $this->cache->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function deleteItem(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->cache->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->cache->save($this->tag($item));
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->cache->saveDeferred($this->tag($item));
    }

    public function commit(): bool
    {
        return $this->cache->commit();
    }

    public function invalidateTag(string $tag): bool
    {
        return $this->cache->invalidateTag($tag);
    }

    public function invalidateTags(array $tags): bool
    {
        return $this->cache->invalidateTags($tags);
    }

    private function tag(CacheItemInterface $item): TaggableCacheItemInterface
    {
        if (!$item instanceof TaggableCacheItemInterface) {
            throw new \InvalidArgumentException('Cache items must implement TaggableCacheItemInterface.');
        }

        return $item->setTags($this->tags);
    }
}
