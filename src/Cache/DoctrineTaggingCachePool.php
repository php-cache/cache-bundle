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
class DoctrineTaggingCachePool implements CacheItemPoolInterface
{
    /**
     * @type CacheItemPoolInterface|TaggablePoolInterface
     */
    private $cache;

    /**
     * @param TaggablePoolInterface $cache
     */
    public function __construct(TaggablePoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @{@inheritdoc}
     */
    public function getItem($key)
    {
        return $this->cache->getItem($key, ['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->cache->getItems($keys, ['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function hasItem($key)
    {
        return $this->cache->hasItem($key, ['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function clear()
    {
        return $this->cache->clear(['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->cache->deleteItem($key, ['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->cache->deleteItems($keys, ['doctrine']);
    }

    /**
     * @{@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save($item);
    }

    /**
     * @{@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->cache->saveDeferred($item);
    }

    /**
     * @{@inheritdoc}
     */
    public function commit()
    {
        return $this->cache->commit();
    }
}
