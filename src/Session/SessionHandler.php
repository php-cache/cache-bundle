<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Session;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Class SessionHandler
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @var CacheItemPoolInterface Cache driver.
     */
    private $cache;

    /**
     * @var integer Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Key prefix for shared environments.
     */
    private $prefix;

    /**
     * Constructor.
     *
     * List of available options:
     *  * prefix: The prefix to use for the cache keys in order to avoid collision
     *  * expiretime: The time to live in seconds
     *
     * @param CacheItemPoolInterface $cache   A Cache instance
     * @param array $options An associative array of cache options
     *
     * @throws \InvalidArgumentException When unsupported options are passed
     */
    public function __construct(CacheItemPoolInterface $cache, array $options = array())
    {
        $this->cache = $cache;

        $this->ttl    = isset($options['ttl']) ? (int) $options['ttl'] : 86400;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2ses_';
    }

    /**
     * {@inheritDoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($sessionId)
    {
        $item = $this->cache->getItem($this->prefix.$sessionId);
        if ($item->isHit()) {
            return $item->get();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function write($sessionId, $data)
    {
        $item = $this->cache->getItem($this->prefix . $sessionId);
        $item->set($data)
            ->expiresAfter($this->ttl);

        return $this->cache->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($sessionId)
    {
        return $this->cache->deleteItem($this->prefix . $sessionId);
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        // not required here because cache will auto expire the records anyhow.
        return true;
    }
}
