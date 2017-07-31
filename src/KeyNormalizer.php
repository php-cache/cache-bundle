<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle;

/**
 * A class to normalize cache keys.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class KeyNormalizer
{
    /**
     * Remove all characters that is not supported by PSR6.
     *
     * @param $key
     */
    public static function onlyValid($key)
    {
        return preg_replace('|[^A-Za-z0-9_\.]|', '', $key);
    }

    /**
     * Remove all characters that are marked as reserved in PSR6.
     *
     * @param string $key
     */
    public static function noInvalid($key)
    {
        return preg_replace('|[\{\}\(\)/\\\@\:]|', '', $key);
    }
}
