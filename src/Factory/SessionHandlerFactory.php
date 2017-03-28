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

use Cache\CacheBundle\Cache\FixedTaggingCachePool;
use Cache\SessionHandler\Psr6SessionHandler;
use Cache\Taggable\TaggablePSR6PoolAdapter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SessionHandlerFactory
{
    /**
     * @param CacheItemPoolInterface $pool
     * @param array                  $config
     *
     * @return Psr6SessionHandler
     */
    public static function get(CacheItemPoolInterface $pool, array $config)
    {
        if ($config['use_tagging']) {
            $pool = new FixedTaggingCachePool(TaggablePSR6PoolAdapter::makeTaggable($pool), ['session']);
        }

        return new Psr6SessionHandler($pool, $config);
    }
}
