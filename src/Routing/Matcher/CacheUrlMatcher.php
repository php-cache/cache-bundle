<?php

/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Routing\Matcher;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Aequasi\Bundle\CacheBundle\Service\CacheService;

/**
 * Class CacheUrlMatcher
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheUrlMatcher extends UrlMatcher
{
    /**
     * @var CacheService
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

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $match = parent::match($pathInfo);
        $this->cache->save($key, $match, 60 * 60 * 24 * 7);

        return $match;
    }

    /**
     * @param CacheService $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheService
     */
    public function getCache()
    {
        return $this->cache;
    }
}
