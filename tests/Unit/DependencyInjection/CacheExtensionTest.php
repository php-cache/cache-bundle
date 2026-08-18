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

namespace Cache\CacheBundle\Tests\Unit\DependencyInjection;

use Cache\CacheBundle\DependencyInjection\CacheExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CacheExtensionTest extends TestCase
{
    public function testUsesCacheAlias()
    {
        self::assertSame('cache', (new CacheExtension())->getAlias());
    }

    public function testRegistersRetainedIntegrations()
    {
        $container = $this->createContainer(false);

        (new CacheExtension())->load([[
            'session' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
            ],
            'router' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('cache.service.session'));
        self::assertTrue($container->hasDefinition('cache.session_lock'));
        $lockDefinition = $container->getDefinition('cache.session_lock');
        $lockFactory = $lockDefinition->getArgument(0);
        self::assertInstanceOf(Reference::class, $lockFactory);
        self::assertSame('lock.factory', (string) $lockFactory);
        self::assertSame(300, $lockDefinition->getArgument(1));
        $sessionLock = $container->getDefinition('cache.service.session')->getArgument(1);
        self::assertInstanceOf(Reference::class, $sessionLock);
        self::assertSame('cache.session_lock', (string) $sessionLock);
        self::assertTrue($container->hasDefinition('cache.service.router'));
        self::assertTrue($container->hasDefinition('Cache\\CacheBundle\\Command\\CacheFlushCommand'));
        self::assertSame(['cache.pool'], $container->getParameter('cache.provider_service_ids'));
        self::assertFalse($container->hasParameter('cache.doctrine'));
        self::assertFalse($container->hasDefinition('cache.data_collector'));
    }

    public function testDebugModeEnablesTheDataCollectorByDefault()
    {
        $container = $this->createContainer(true);

        (new CacheExtension())->load([], $container);

        $definition = $container->getDefinition('cache.data_collector');
        self::assertSame([[
            'template' => '@Cache/Collector/cache.html.twig',
            'id' => 'php-cache',
        ]], $definition->getTag('data_collector'));
    }

    public function testDataCollectorCanBeDisabledInDebugMode()
    {
        $container = $this->createContainer(true);

        (new CacheExtension())->load([[
            'data_collector' => ['enabled' => false],
        ]], $container);

        self::assertFalse($container->hasDefinition('cache.data_collector'));
    }

    private function createContainer(bool $debug): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $debug);

        return $container;
    }
}
