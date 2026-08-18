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
