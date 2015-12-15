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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class LoggingCachePool implements CacheItemPoolInterface
{
    /**
     * @type array
     */
    private $calls = [];

    /**
     * @type CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * LoggingCachePool constructor.
     *
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    public function getItem($key)
    {
        $call         = $this->timeCall(__FUNCTION__, [$key]);
        $result       = $call->result;
        $call->result = sprintf('<DATA:%s>', gettype($result));

        $this->calls[] = $call;

        return $result;
    }

    public function hasItem($key)
    {
        $call          = $this->timeCall(__FUNCTION__, [$key]);
        $this->calls[] = $call;

        return $call->result;
    }

    public function deleteItem($key)
    {
        $call          = $this->timeCall(__FUNCTION__, [$key]);
        $this->calls[] = $call;

        return $call->result;
    }

    public function save(CacheItemInterface $item)
    {
        $itemClone = clone $item;
        $itemClone->set(sprintf('<DATA:%s', gettype($item->get())));

        $call            = $this->timeCall(__FUNCTION__, [$item]);
        $call->arguments = ['<CacheItem>', $itemClone];
        $this->calls[]   = $call;

        return $call->result;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return object
     */
    private function timeCall($name, array $arguments = null)
    {
        $start  = microtime(true);
        $result = call_user_func_array([$this->cachePool, $name], $arguments);
        $time   = microtime(true) - $start;

        $object = (object) compact('name', 'arguments', 'start', 'time', 'result');

        return $object;
    }

    public function getItems(array $keys = [])
    {
        $call         = $this->timeCall(__FUNCTION__, [$keys]);
        $result       = $call->result;
        $call->result = sprintf('<DATA:%s>', gettype($result));

        $this->calls[] = $call;

        return $result;
    }

    public function clear()
    {
        $call          = $this->timeCall(__FUNCTION__);
        $this->calls[] = $call;

        return $call->result;
    }

    public function deleteItems(array $keys)
    {
        $call          = $this->timeCall(__FUNCTION__, [$keys]);
        $this->calls[] = $call;

        return $call->result;
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        $itemClone = clone $item;
        $itemClone->set(sprintf('<DATA:%s', gettype($item->get())));

        $call            = $this->timeCall(__FUNCTION__, [$item]);
        $call->arguments = ['<CacheItem>', $itemClone];
        $this->calls[]   = $call;

        return $call->result;
    }

    public function commit()
    {
        $call          = $this->timeCall(__FUNCTION__);
        $this->calls[] = $call;

        return $call->result;
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return $this->calls;
    }
}
