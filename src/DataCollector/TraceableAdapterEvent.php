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

namespace Cache\CacheBundle\DataCollector;

final class TraceableAdapterEvent
{
    public float $end;

    public mixed $result = null;

    public int $hits = 0;

    public int $misses = 0;

    public function __construct(
        public readonly string $name,
        public mixed $argument,
        public readonly float $start = 0.0,
    ) {
    }
}
