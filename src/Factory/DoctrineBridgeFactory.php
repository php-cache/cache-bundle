<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Factory;

use Cache\Bridge\DoctrineCacheBridge;
use Cache\CacheBundle\Cache\FixedTaggingCachePool;
use Cache\Taggable\TaggablePSR6PoolAdapter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DoctrineBridgeFactory
{
    /**
     * @param CacheItemPoolInterface $pool
     * @param array                  $config
     * @param array                  $tags
     *
     * @return DoctrineCacheBridge
     */
    public static function get(CacheItemPoolInterface $pool, $config, array $tags)
    {
        if ($config['use_tagging']) {
            $pool = new FixedTaggingCachePool(TaggablePSR6PoolAdapter::makeTaggable($pool), $tags);
        }

        return new DoctrineCacheBridge($pool);
    }
}
