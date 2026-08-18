<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Factory;

use Cache\CacheBundle\Cache\FixedTaggingCachePool;
use Cache\SessionHandler\Psr6SessionHandler;
use Cache\SessionHandler\SessionLockInterface;
use Cache\Taggable\TaggablePSR6PoolAdapter;
use Psr\Cache\CacheItemPoolInterface;

final class SessionHandlerFactory
{
    /**
     * @param array{use_tagging: bool, prefix: string, ttl: int|null} $config
     */
    public static function get(CacheItemPoolInterface $pool, SessionLockInterface $lock, array $config): Psr6SessionHandler
    {
        if ($config['use_tagging']) {
            $pool = new FixedTaggingCachePool(TaggablePSR6PoolAdapter::makeTaggable($pool), ['session']);
        }

        return new Psr6SessionHandler($pool, $lock, array_filter([
            'prefix' => $config['prefix'],
            'ttl' => $config['ttl'],
        ], static fn (mixed $value): bool => null !== $value));
    }
}
