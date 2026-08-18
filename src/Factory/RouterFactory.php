<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Factory;

use Cache\CacheBundle\Routing\CachingRouter;
use Cache\Prefixed\PrefixedCachePool;
use Cache\Taggable\TaggablePSR6PoolAdapter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\RouterInterface;

final class RouterFactory
{
    /**
     * @param array{ttl: int, use_tagging: bool, prefix: string} $config
     */
    public static function get(CacheItemPoolInterface $pool, RouterInterface $router, array $config): CachingRouter
    {
        if ($config['use_tagging']) {
            $pool = TaggablePSR6PoolAdapter::makeTaggable($pool);
        }

        if ('' !== $config['prefix']) {
            $pool = new PrefixedCachePool($pool, $config['prefix']);
        }

        return new CachingRouter($pool, $router, $config);
    }
}
