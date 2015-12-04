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
            'Aequasi\Bundle\CacheBundle\Service\CacheService',
            $container->get($this->getAlias().'.instance.default')
        );
        $this->assertInstanceOf(
            'Doctrine\Common\Cache\Cache',
            $container->get($this->getAlias().'.instance.default')->getCache()
        );

        $this->assertInstanceOf(
            DoctrineCacheBridge::class,
            $container->get($this->getAlias().'.instance.default')->getCache()
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
