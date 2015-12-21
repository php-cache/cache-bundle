<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Cache;

use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * This class is a decorator for a TaggablePoolInterface. It tags everything with 'doctrine'.
 * Use this class with the DoctrineBridge.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class FixedTaggingCachePool implements CacheItemPoolInterface
{
    /**
     * @type CacheItemPoolInterface|TaggablePoolInterface
     */
    private $cache;

    /**
     * @type array
     */
    private $tags;

    /**
     * @param TaggablePoolInterface $cache
     * @param array                 $tags
     */
    public function __construct(TaggablePoolInterface $cache, array $tags)
    {
        $this->cache = $cache;
        $this->tags  = $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        return $this->cache->getItem($key, $this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->cache->getItems($keys, $this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        return $this->cache->hasItem($key, $this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->cache->clear($this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key, $this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys, $this->tags);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->cache->saveDeferred($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->cache->commit();
    }
}
