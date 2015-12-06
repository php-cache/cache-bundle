<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Routing\Matcher;

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
    const CACHE_LIFETIME = 604800; // a week

    /**
     * @var CacheItemPoolInterface
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
        $item = $this->cache->getItem($key);
            $item->set($match)
                ->expiresAfter(self::CACHE_LIFETIME);

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
