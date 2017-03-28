<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Cache\Recording;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * A pool that logs and collects all your cache calls.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 */
class CachePool implements CacheItemPoolInterface
{
    /**
     * @type CacheItemPoolInterface
     */
    protected $pool;

    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @type string
     */
    private $name;

    /**
     * @type string
     */
    private $level = 'info';

    /**
     * @type array calls
     */
    private $calls = [];

    /**
     * @param CacheItemPoolInterface $pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $event = $this->start(__FUNCTION__, $key);
        try {
            $item = $this->pool->getItem($key);
        } finally {
            $event->end = microtime(true);
        }
        if ($item->isHit()) {
            ++$event->hits;
        } else {
            ++$event->misses;
        }
        $event->result = $item->get();

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        $event = $this->start(__FUNCTION__, $key);
        try {
            $event->result = $this->pool->hasItem($key);
        } finally {
            $event->end = microtime(true);
        }

        if (!$event->result) {
            ++$event->misses;
        }

        return $event->result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        $event = $this->start(__FUNCTION__, $key);
        try {
            return $event->result = $this->pool->deleteItem($key);
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        $event = $this->start(__FUNCTION__, $item);
        try {
            return $event->result = $this->pool->save($item);
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $event = $this->start(__FUNCTION__, $item);
        try {
            return $event->result = $this->pool->saveDeferred($item);
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        $event = $this->start(__FUNCTION__, $keys);
        try {
            $result = $this->pool->getItems($keys);
        } finally {
            $event->end = microtime(true);
        }
        $f = function () use ($result, $event) {
            $event->result = [];
            foreach ($result as $key => $item) {
                if ($item->isHit()) {
                    ++$event->hits;
                } else {
                    ++$event->misses;
                }
                $event->result[$key] = $item->get();
                yield $key => $item;
            }
        };

        return $f();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result = $this->pool->clear();
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $event = $this->start(__FUNCTION__, $keys);
        try {
            return $event->result = $this->pool->deleteItems($keys);
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result = $this->pool->commit();
        } finally {
            $event->end = microtime(true);
        }
    }

    public function getCalls()
    {
        return $this->calls;
    }

    protected function start($name, $argument = null)
    {
        $this->calls[]   = $event   = new TraceableAdapterEvent();
        $event->name     = $name;
        $event->argument = $argument;
        $event->start    = microtime(true);

        return $event;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $level
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }
}

/**
 * @internal
 */
class TraceableAdapterEvent
{
    public $name;
    public $argument;
    public $start;
    public $end;
    public $result;
    public $hits   = 0;
    public $misses = 0;
}
