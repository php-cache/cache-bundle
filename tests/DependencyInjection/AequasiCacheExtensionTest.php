<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\DependencyInjection;

use Cache\CacheBundle\Tests\TestCase;
use Aequasi\Cache\DoctrineCacheBridge;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class AequasiCacheExtensionTest
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class AequasiCacheExtensionTest extends TestCase
{

    /**
     *
     */
    public function testServiceBuilder()
    {
        $container = $this->createContainerFromFile('service');

        $this->assertTrue($container->hasDefinition($this->getAlias().'.instance.default'));
        $this->assertTrue($container->hasAlias($this->getAlias().'.default'));

        $this->assertInstanceOf(
            CacheItemPoolInterface::class,
            $container->get($this->getAlias().'.instance.default')
        );

        $this->assertInstanceOf(
            DoctrineCacheBridge::class,
            $container->get($this->getAlias().'.instance.default.bridge')
        );
    }

    /**
     *
     */
    public function testRouterBuilder()
    {
        $container = $this->createContainerFromFile('router');

        $config = $container->getParameter($this->getAlias().'.router');

        $this->assertTrue(isset($config['enabled']));
        $this->assertTrue(isset($config['instance']));

        $this->assertTrue($config['enabled']);
        $this->assertEquals($config['instance'], 'default');

        $this->assertEquals('Cache\CacheBundle\Routing\Router', $container->getParameter('router.class'));
    }

    /**
     * @return string
     */
    private function getAlias()
    {
        return 'aequasi_cache';
    }
}
