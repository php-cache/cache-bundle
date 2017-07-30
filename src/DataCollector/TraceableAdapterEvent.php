<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DataCollector;

/**
 * @internal
 */
class TraceableAdapterEvent
{
    public $name;
    public $argument;
    public $start;
    public $end;
    public $result;
    public $hits   = 0;
    public $misses = 0;
}
