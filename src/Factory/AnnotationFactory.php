<?php

namespace Cache\CacheBundle\Factory;

use Cache\Bridge\DoctrineCacheBridge;
use Cache\CacheBundle\Cache\FixedTaggingCachePool;
use Psr\Cache\CacheItemPoolInterface;

/**
 *
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AnnotationFactory
{
    /**
     * @param CacheItemPoolInterface $pool
     * @param array $config
     *
     * @return DoctrineCacheBridge
     */
    public static function get(CacheItemPoolInterface $pool, $config)
    {
        if ($config['use_tagging']) {
            $pool = new FixedTaggingCachePool($pool, 'annotation');
        }

        return new DoctrineCacheBridge($pool);
    }
}