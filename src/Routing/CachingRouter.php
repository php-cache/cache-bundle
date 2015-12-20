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

use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CachingRouter implements RouterInterface
{
    /**
     * @type CacheItemPoolInterface
     */
    private $cache;

    /**
     * @type int
     */
    private $ttl;

    /**
     * @type RouterInterface
     */
    private $router;

    /**
     * @param CacheItemPoolInterface $cache
     * @param RouterInterface        $router
     * @param $ttl
     */
    public function __construct(CacheItemPoolInterface $cache, RouterInterface $router, $ttl)
    {
        $this->cache  = $cache;
        $this->ttl    = $ttl;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $cacheItem = $this->getCacheItemMatch($pathinfo);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        // Get the result form the router
        $result = $this->router->match($pathinfo);

        // Save the result
        $cacheItem->set($result)
            ->expiresAfter($this->ttl);
        $this->cache->save($cacheItem);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $cacheItem = $this->getCacheItemGenerate($name, $parameters, $referenceType);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        // Get the result form the router
        $result = $this->router->generate($name, $parameters, $referenceType);

        // Save the result
        $cacheItem->set($result)
            ->expiresAfter($this->ttl);
        $this->cache->save($cacheItem);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * Get a cache item for a call to match().
     *
     * @param string $pathinfo
     *
     * @return \Psr\Cache\CacheItemInterface
     */
    private function getCacheItemMatch($pathinfo)
    {
        /** @type RequestContext $c */
        $c   = $this->getContext();
        $key = sprintf('routing:%s:%s:%s:%s', $c->getMethod(), $c->getHost(), $pathinfo, $c->getQueryString());

        return $this->getCacheItemFromKey($key, 'match');
    }

    /**
     * Get a cache item for a call to generate().
     *
     * @param $name
     * @param array $parameters
     * @param $referenceType
     *
     * @return \Psr\Cache\CacheItemInterface
     */
    private function getCacheItemGenerate($name, array $parameters, $referenceType)
    {
        sort($parameters);
        $key = sprintf('generate:%s:%s:%s', $name, json_encode($parameters), $referenceType ? 'true' : 'false');

        return $this->getCacheItemFromKey($key, 'generate');
    }

    /**
     * Passes through all unknown calls onto the router object.
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->router, $method], $args);
    }

    /**
     * @param string $key
     * @param string $tag
     *
     * @return \Psr\Cache\CacheItemInterface
     */
    private function getCacheItemFromKey($key, $tag)
    {
        if ($this->cache instanceof TaggablePoolInterface) {
            $item = $this->cache->getItem($key, ['router', $tag]);
        } else {
            $item = $this->cache->getItem($key);
        }

        return $item;
    }
}
