<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Tests\Functional;

use Cache\Bridge\Doctrine\DoctrineCacheBridge;
use Cache\CacheBundle\Bridge\SymfonyValidatorBridge;
use Cache\CacheBundle\CacheBundle;
use Cache\CacheBundle\Routing\CachingRouter;
use Cache\SessionHandler\Psr6SessionHandler;
use Nyholm\BundleTest\BaseBundleTestCase;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BundleInitializationTest extends BaseBundleTestCase
{
    protected function getBundleClass()
    {
        return CacheBundle::class;
    }

    protected function setUp()
    {
        parent::setUp();
        $kernel = $this->createKernel();
        $kernel->addConfigFile(__DIR__.'/config.yml');

        if (Kernel::MAJOR_VERSION < 4) {
            $kernel->addConfigFile(__DIR__.'/sf2_and_3.yml');
        }
    }

    public function testInitBundle()
    {
        $this->bootKernel();
        $container = $this->getContainer();

        $this->assertTrue($container->hasParameter('cache.provider_service_ids'));

        if (Kernel::MAJOR_VERSION < 4) {
            $this->assertInstanceOf(DoctrineCacheBridge::class, $container->get('cache.service.annotation'));
            $this->assertInstanceOf(DoctrineCacheBridge::class, $container->get('cache.service.serializer'));
            $this->assertInstanceOf(SymfonyValidatorBridge::class, $container->get('cache.service.validation'));
            $this->assertInstanceOf(Psr6SessionHandler::class, $container->get('cache.service.session'));
            $this->assertInstanceOf(CachingRouter::class, $container->get('cache.service.router'));
        }
    }
}
