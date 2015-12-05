<?php

/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Routing;

use Aequasi\Bundle\CacheBundle\Routing\Matcher\CacheUrlMatcher;
use Aequasi\Cache\CachePool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;

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
            if ($this->cache->hasItem($key)) {
                $collection = $this->cache->getItem($key)->get();
                if ($collection !== null) {
                    $this->collection = $collection;

                    return $this->collection;
                }
            }

            $this->collection = parent::getRouteCollection();
            $item = $this->cache->getItem($key);
            $item->set($this->collection)
                ->expiresAfter(self::CACHE_LIFETIME);
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
}
