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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class CacheUrlMatcher
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheUrlMatcher extends UrlMatcher
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cachePool;

    /**
     * @type int
     */
    protected $ttl;

    /**
     * CacheUrlMatcher constructor.
     *
     * @param CacheItemPoolInterface $cachePool
     * @param int                    $ttl
     * @param RouteCollection        $routes
     * @param RequestContext         $context
     */
    public function __construct(
        CacheItemPoolInterface $cachePool,
        $ttl,
        RouteCollection $routes,
        RequestContext $context
    ) {
        $this->cachePool = $cachePool;
        $this->ttl       = $ttl;
        parent::__construct($routes, $context);
    }

    /**
     * @param string $pathInfo
     *
     * @return array
     */
    public function match($pathInfo)
    {
        $host   = strtr($this->context->getHost(), '.', '_');
        $method = strtolower($this->context->getMethod());
        $key    = 'route_'.$method.'_'.$host.'_'.$pathInfo;

        $cacheItem = $this->cachePool->getItem($key);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $match = parent::match($pathInfo);
        $cacheItem->set($match)
            ->expiresAfter($this->ttl);
        $this->cachePool->save($cacheItem);

        return $match;
    }
}
