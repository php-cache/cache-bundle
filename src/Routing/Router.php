<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Routing;

use Cache\CacheBundle\Routing\Matcher\CacheUrlMatcher;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Router
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Router extends BaseRouter
{
    const CACHE_LIFETIME = 604800; // a week

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @type int
     */
    protected $ttl;

    /**
     * @return CacheUrlMatcher|null|\Symfony\Component\Routing\Matcher\UrlMatcherInterface
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        $matcher = new CacheUrlMatcher($this->getRouteCollection(), $this->context);
        $matcher->setCache($this->cache);

        return $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        $key = 'route_collection';

        if (null === $this->collection) {
            $cacheItem = $this->cache->getItem($key);
            if ($cacheItem->isHit()) {
                $this->collection = $cacheItem->get();
            } else {
                $this->collection = parent::getRouteCollection();
                $cacheItem->set($this->collection);
                $cacheItem->expiresAfter($this->getTtl());

                $this->cache->save($cacheItem);
            }
        }

        return $this->collection;
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return Router
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}
