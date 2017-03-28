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

use Cache\TagInterop\TaggableCacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class TaggablePool extends CachePool implements TaggableCacheItemPoolInterface
{
    /**
     * {@inheritdoc}
     */
    public function invalidateTag($tag)
    {
        $event = $this->start(__FUNCTION__, $tag);
        try {
            return $event->result = $this->pool->invalidateTag($tag);
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
        $event = $this->start(__FUNCTION__, $tags);
        try {
            return $event->result = $this->pool->invalidateTags($tags);
        } finally {
            $event->end = microtime(true);
        }
    }
}
