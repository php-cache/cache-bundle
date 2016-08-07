<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'Cache\CacheBundle\Tests\Functional\app\AppKernel';
    }

    protected static function createKernel(array $options = [])
    {
        $class = self::getKernelClass();

        return new $class(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }
}
