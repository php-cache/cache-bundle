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
 * An interface for a cache proxy. A cache proxy is created when we profile a cache pool.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface CacheProxyInterface
{
    public function __getCalls();

    public function __setName($name);
}
