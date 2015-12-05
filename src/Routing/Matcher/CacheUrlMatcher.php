<?php

/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Routing\Matcher;

use Aequasi\Cache\CachePool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/**
 * Class CacheUrlMatcher
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheUrlMatcher extends UrlMatcher
{
    /**
     * @var CachePool
     */
    protected $cache;

    /**
     * @param string $pathInfo
     *
     * @return array
     */
    public function match($pathInfo)
    {
        $host   = strtr($this->context->getHost(), '.', '_');
        $method = strtolower($this->context->getMethod());
        $key    = 'route_' . $method . '_' . $host . '_' . $pathInfo;

        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key)->get();
        }

        $match = parent::match($pathInfo);
        $this->cache->saveItem($key, $match, 60 * 60 * 24 * 7);

        return $match;
    }

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
}
