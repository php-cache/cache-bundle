<?php
/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @copyright Underground Elephant
 */

namespace Aequasi\Bundle\CacheBundle\Tests\DependencyInjection;

use Aequasi\Bundle\CacheBundle\Tests\TestCase;

class AequasiCacheExtensionTest extends TestCase
{

    public function testServiceBuilder()
    {
        $container = $this->createContainerFromFile( 'service' );

        $config = $container->getParameter( $this->getAlias() . '.instance' );

        foreach (array( 'memcached', 'redis' ) as $type) {

            $this->assertTrue( isset( $config[ $type ] ) );

            $this->assertTrue( $container->hasDefinition( $this->getAlias() . '.instance.' . $type ) );
            $this->assertTrue( $container->hasAlias( $this->getAlias() . '.' . $type ) );

            $this->assertInstanceOf(
                 'Aequasi\Bundle\CacheBundle\Service\CacheService',
                     $container->get( $this->getAlias() . '.instance.' . $type )
            );
            $this->assertInstanceOf(
                 'Doctrine\Common\Cache\Cache',
                     $container->get( $this->getAlias() . '.instance.' . $type )
                               ->getCache()
            );

            $function = 'get' . ucwords( $type );
            $this->assertInstanceOf(
                 ucwords( $type ),
                     $container->get( $this->getAlias() . '.instance.' . $type )
                               ->getCache()
                               ->{$function}()
            );
        }
    }

    public function testRouterBuilder()
    {
        $container = $this->createContainerFromFile( 'router' );

        $config = $container->getParameter( $this->getAlias() . '.router' );

        $this->assertTrue( isset( $config[ 'enabled' ] ) );
        $this->assertTrue( isset( $config[ 'instance' ] ) );

        $this->assertTrue( $config[ 'enabled' ] );
        $this->assertEquals( $config[ 'instance' ], 'default' );

        $this->assertEquals( 'Aequasi\Bundle\CacheBundle\Routing\Router', $container->getParameter( 'router.class' ) );
    }

    private function getAlias()
    {
        return 'aequasi_cache';
    }
} 