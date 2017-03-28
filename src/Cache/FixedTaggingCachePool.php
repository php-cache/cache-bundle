<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Cache;

use Cache\Taggable\TaggableItemInterface;
use Cache\Taggable\TaggablePoolInterface;
use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * This class is a decorator for a TaggableCacheItemPoolInterface. It tags everything with predefined tags.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class FixedTaggingCachePool implements TaggableCacheItemPoolInterface
{
    /**
     * @type TaggableCacheItemPoolInterface
     */
    private $cache;

    /**
     * @type array
     */
    private $tags;

    /**
     * @param TaggableCacheItemPoolInterface $cache
     * @param array                 $tags
     */
    public function __construct(TaggableCacheItemPoolInterface $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags  = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        return $this->cache->getItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->cache->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        return $this->cache->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->cache->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof TaggableCacheItemInterface) {
            throw new InvalidArgumentException('Cache items are not transferable between pools. Item MUST implement TaggableCacheItemInterface.');
        }

        $item->setTags($this->tags);

        return $this->cache->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if (!$item instanceof TaggableCacheItemInterface) {
            throw new InvalidArgumentException('Cache items are not transferable between pools. Item MUST implement TaggableCacheItemInterface.');
        }

        $item->setTags($this->tags);

        return $this->cache->saveDeferred($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->cache->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTag($tag)
    {
        return $this->invalidateTag($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        return $this->cache-$this->invalidateTags($tags);
    }
}
