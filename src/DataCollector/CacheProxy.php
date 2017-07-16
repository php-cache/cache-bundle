<?php

namespace Cache\CacheBundle\DataCollector;

/**
 * An interface for a cache proxy. A cache proxy is created when we profile a cache pool.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface CacheProxy
{
    public function __getCalls();

    public function __setName($name);
}
