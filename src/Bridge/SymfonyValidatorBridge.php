<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Bridge;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyValidatorBridge implements CacheInterface
{
    /**
     * @type CacheItemPoolInterface
     */
    private $pool;

    /**
     * DoctrineCacheBridge constructor.
     *
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->pool = $cachePool;
    }

    /**
     * {@inheritdoc}
     */
    public function has($class)
    {
        return $this->pool->hasItem($this->normalizeKey($class));
    }

    /**
     * {@inheritdoc}
     */
    public function read($class)
    {
        $item = $this->pool->getItem($this->normalizeKey($class));

        if (!$item->isHit()) {
            return false;
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function write(ClassMetadata $metadata)
    {
        $item = $this->pool->getItem($this->normalizeKey($metadata->getClassName()));
        $item->set($metadata);
        $this->pool->save($item);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function normalizeKey($key)
    {
        return preg_replace('|[\\\/]|', '.', $key);
    }
}
