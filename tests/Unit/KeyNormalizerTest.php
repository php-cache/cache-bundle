<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\Unit;

use Cache\CacheBundle\KeyNormalizer;

class KeyNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnlyValid()
    {
        $input    = '%foo!bar-';
        $expected = 'foobar';
        $this->assertEquals($expected, KeyNormalizer::onlyValid($input));
    }

    public function testNoInvalid()
    {
        $input    = '{foo@bar}';
        $expected = 'foobar';
        $this->assertEquals($expected, KeyNormalizer::noInvalid($input));
    }
}
