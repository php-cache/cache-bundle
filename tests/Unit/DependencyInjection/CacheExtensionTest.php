<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\Unit\DependencyInjection;

use Cache\CacheBundle\Tests\Unit\TestCase;

/**
 * Class CacheExtensionTest.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheExtensionTest extends TestCase
{
    public function testRouterBuilder()
    {
        $container = $this->createContainerFromFile('router');

        $config = $container->getParameter('cache.router');

        $this->assertTrue(isset($config['enabled']));

        $this->assertTrue($config['enabled']);
        $this->assertEquals($config['service_id'], 'default');
    }
}
