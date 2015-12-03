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

use Doctrine\Common\Cache\Cache;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DoctrineCacheBridge implements Cache
{
    /**
     * @type CachePool
     */
    private $cachePool;

    /**
     * DoctrineCacheBridge constructor.
     *
     * @param CachePool $cachePool
     */
    public function __construct(CachePool $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    /**
     * @param string $id
     *
     * @return CacheItem|\Psr\Cache\CacheItemInterface
     * @throws Exception\InvalidArgumentException
     */
    public function fetch($id)
    {
        return $this->cachePool->getItem($id)->get();
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function contains($id)
    {
        return $this->cachePool->hasItem($id);
    }

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $lifeTime
     *
     * @return bool|void
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $cacheItem = new CacheItem($id);
        $cacheItem->set($data);
        $cacheItem->expiresAfter($lifeTime === 0 ? null : $lifeTime);

        return $this->cachePool->save($cacheItem);
    }

    /**
     * @param string $id
     *
     * @return bool
     * @throws Exception\InvalidArgumentException
     */
    public function delete($id)
    {
        return $this->cachePool->deleteItem($id);
    }

    /**
     * @return array
     */
    public function getStats()
    {
        return [];
    }
}
