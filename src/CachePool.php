<?php

/**
 * This file is part of cache-bundle
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Aequasi\Bundle\CacheBundle;

use Aequasi\Bundle\CacheBundle\Exception\BadMethodCallException;
use Aequasi\Bundle\CacheBundle\Exception\InvalidArgumentException;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FlushableCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CachePool implements CacheItemPoolInterface
{
    /**
     * @type Cache
     */
    private $cache;

    /**
     * @type array|CacheItem|CacheItemInterface
     */
    private $deferred = [];

    /**
     * CachePool constructor.
     *
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Passed key is invalid");
        }

        $data = $this->cache->fetch($key);

        return new CacheItem($key, $data['value'], new \DateTime($data['expiration']));
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        if ($this->cache instanceof FlushableCache) {
            return $this->cache->flushAll();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException("Passed key is invalid");
        }

        return $this->cache->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys)
    {
        $deleted = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * @param CacheItem $item
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->cache->save(
            $item->getKey(),
            ['value' => $item->get(), 'expiration' => $item->getExpirationDate()]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[] = $item;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $saved = true;
        foreach ($this->deferred as $key => $item) {
            if (!$this->save($item)) {
                $saved = false;
            } else {
                unset($this->deferred[$key]);
            }
        }

        $this->deferred = array_values($this->deferred);

        return $saved;
    }
}
