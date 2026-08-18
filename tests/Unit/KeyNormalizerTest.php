<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit;

use Cache\CacheBundle\KeyNormalizer;
use PHPUnit\Framework\TestCase;

final class KeyNormalizerTest extends TestCase
{
    public function testOnlyValid()
    {
        self::assertSame('foobar', KeyNormalizer::onlyValid('%foo!bar-'));
    }

    public function testNoInvalid()
    {
        self::assertSame('foobar', KeyNormalizer::noInvalid('{foo@bar}'));
    }
}
