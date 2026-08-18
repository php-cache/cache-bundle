<?php

declare(strict_types=1);

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
