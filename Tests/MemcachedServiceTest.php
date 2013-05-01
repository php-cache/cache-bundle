<?php
/**
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @date 2013
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */
namespace Aequasi\Bundle\MemcachedBundle\Tests;

/**
 * Class MemcachedServiceTest
 *
 * @package Doctrine\Bundle\DoctrineBundle\Tests
 */
class MemcachedServiceTest extends TestCase
{

	/**
	 *
	 */
	public function testMemcachedService()
	{
		$container = $this->createYamlBundleTestContainer();

		/** @var \Aequasi\Bundle\MemcachedBundle\Service\MemcachedService $memcached */
		$memcached = $container->get( 'memcached' );

		$this->assertFalse( $memcached->get( 'test' ) );
		$this->assertTrue( $memcached->set( 'test', 'testValue', 1 ) );
		sleep( 2 );
		$this->assertFalse( $memcached->get( 'test' ) );

		$this->assertTrue( $memcached->set( 'test', 'testValue', 5 ) );
		$this->assertEquals( $memcached->get( 'test' ), 'testValue' );

		$this->assertTrue( $memcached->delete( 'test' ) );
		$this->assertTrue( $memcached->deleteAll() );
	}
}