<?php

declare(strict_types=1);

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
