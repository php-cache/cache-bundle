<?php

namespace Cache\CacheBundle\Bridge;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 *
 *
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
        return $this->pool->hasItem($class);
    }

    /**
     * {@inheritdoc}
     */
    public function read($class)
    {
        $item = $this->pool->getItem($class);

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
        $item = $this->pool->getItem($metadata->getClassName());
        $item->set($metadata);
        $this->pool->save($item);
    }
}