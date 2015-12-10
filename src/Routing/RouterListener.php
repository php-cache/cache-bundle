<?php

namespace Cache\CacheBundle\Routing;

use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RouterListener
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param CacheItemPoolInterface $cache
     * @param int                    $ttl
     */
    public function __construct(CacheItemPoolInterface $cache, $ttl)
    {
        $this->cache = $cache;
        $this->ttl   = $ttl;
    }

    /**
     * Before routing, try to fetch route result from cache.
     *
     * @param GetResponseEvent $event
     */
    public function onBeforeRouting(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->attributes->has('_controller')) {
            // routing is already done
            return;
        }

        $item = $this->getCacheItem($request);
        if (!$item->isHit()) {
            return;
        }

        $request->attributes->add($item->get());
        $request->attributes->set('_cache_hit', true);
    }

    /**
     * After the routing, make sure to store the result in cache.
     *
     * @param GetResponseEvent $event
     */
    public function onAfterRouting(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_controller')) {
            // routing has not taken place
            return;
        }

        if ($request->attributes->has('_cache_hit')) {
            $request->attributes->remove('_cache_hit');
            // object is in cache all ready
            return;
        }

        // Save to the cache
        $item = $this->getCacheItem($request);
        $item->set($request->attributes->all());
        $this->cache->save($item);
    }

    /**
     * Get a good key that varies on method, host, path info etc etc.
     *
     * @param Request $request
     *
     * @return string
     */
    private function createCacheKey(Request $request)
    {
        $key = sprintf('route:%s:%s:%s',$request->getMethod(),$request->getHost(),$request->getPathInfo());

        // This might be optional
        //$key.=':'.$this->implodeRecursive('|', $request->query->all());

        return $key;
    }

    /**
     * @param $separator
     * @param array $array
     *
     * @return string
     */
    private function implodeRecursive($separator, array $array)
    {
        $output = '';
        foreach ($array as $key=>$value) {
            if (is_array($value)) {
                $output.=sprintf('%s%s[%s]', $separator, $key, $this->implodeRecursive($separator, $value));
            } else {
                $output.=$separator.$value;
            }
        }

        return ltrim($output, $separator);
    }

    /**
     * @param Request $request
     *
     * @return \Psr\Cache\CacheItemInterface
     */
    private function getCacheItem(Request $request)
    {
        $key = $this->createCacheKey($request);
        if ($this->cache instanceof TaggablePoolInterface) {
            $item = $this->cache->getItem($key, ['routing']);
        } else {
            $item = $this->cache->getItem($key);
        }

        return $item;
    }
}
