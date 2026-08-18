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

namespace Cache\CacheBundle;

final class KeyNormalizer
{
    public static function onlyValid(string $key): string
    {
        return (string) preg_replace('|[^A-Za-z0-9_.]|', '', $key);
    }

    public static function noInvalid(string $key): string
    {
        return (string) preg_replace('|[{}()/\\@:]+|', '', $key);
    }
}
