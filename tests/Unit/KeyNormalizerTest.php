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
