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

use Cache\Taggable\TaggablePoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class TaggablePool extends CachePool implements TaggablePoolInterface
{
    public function clearTags(array $tags)
    {
        $call = $this->timeCall(__FUNCTION__, [$tags]);
        $this->addCall($call);

        return $call->result;
    }
}
