<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Cache\Recording;

use Cache\Hierarchy\HierarchicalPoolInterface;
use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Create a recording CachePool.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Factory
{
    /**
     * @type int|string
     */
    private $level;

    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param string|int      $level
     */
    public function __construct(LoggerInterface $logger = null, $level = null)
    {
        $this->level  = $level;
        $this->logger = $logger;
    }

    /**
     * Decorate a CachePool with a recorder. Make sure we use a recorder that implements the same functionality
     * as the underling pool.
     *
     * @param CacheItemPoolInterface $pool
     *
     * @return CachePool|HierarchyAndTaggablePool|HierarchyPool|TaggablePool
     */
    public function create($name, CacheItemPoolInterface $pool)
    {
        if ($pool instanceof TaggableCacheItemPoolInterface && $pool instanceof HierarchicalPoolInterface) {
            $recorder = new HierarchyAndTaggablePool($pool);
        } elseif ($pool instanceof TaggableCacheItemPoolInterface) {
            $recorder = new TaggablePool($pool);
        } elseif ($pool instanceof HierarchicalPoolInterface) {
            $recorder = new HierarchyPool($pool);
        } else {
            $recorder = new CachePool($pool);
        }

        $recorder->setName($name);
        $recorder->setLevel($this->level);
        $recorder->setLogger($this->logger);

        return $recorder;
    }
}
