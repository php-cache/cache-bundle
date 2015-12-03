<?php

/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @copyright Underground Elephant
 */

namespace Aequasi\Bundle\CacheBundle\Tests\DependencyInjection;

use Aequasi\Bundle\CacheBundle\Tests\TestCase;
use Aequasi\Cache\DoctrineCacheBridge;
use Doctrine\Common\Cache\ArrayCache;
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

        $this->assertEquals('Aequasi\Bundle\CacheBundle\Routing\Router', $container->getParameter('router.class'));
    }

    /**
     * @return string
     */
    private function getAlias()
    {
        return 'aequasi_cache';
    }
}
