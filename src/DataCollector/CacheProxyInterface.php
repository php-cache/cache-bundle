<?php

declare(strict_types=1);

namespace Cache\CacheBundle\DataCollector;

use Psr\Cache\CacheItemPoolInterface;

interface CacheProxyInterface extends CacheItemPoolInterface
{
    /**
     * @return list<TraceableAdapterEvent>
     */
    public function getCalls(): array;

    public function clearCalls(): void;

    public function getName(): string;
}
