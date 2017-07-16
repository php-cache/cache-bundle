<?php

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
    public $hits = 0;
    public $misses = 0;
}
