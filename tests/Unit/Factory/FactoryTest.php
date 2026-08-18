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

namespace Cache\CacheBundle\Tests\Unit\Factory;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\Factory\RouterFactory;
use Cache\CacheBundle\Factory\SessionHandlerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class FactoryTest extends TestCase
{
    public function testCreatesConfiguredSessionHandler()
    {
        $lock = new RecordingSessionLock();
        $handler = SessionHandlerFactory::get(new ArrayCachePool(), $lock, [
            'use_tagging' => false,
            'prefix' => 'session.',
            'ttl' => 60,
        ]);

        self::assertTrue($handler->write('id', 'data'));
        self::assertSame('data', $handler->read('id'));
        self::assertTrue($handler->close());
        self::assertSame(['id'], $lock->acquired);
        self::assertSame(['id'], $lock->released);
    }

    public function testCreatesTaggableSessionHandler()
    {
        $pool = new ArrayCachePool();
        $handler = SessionHandlerFactory::get($pool, new RecordingSessionLock(), [
            'use_tagging' => true,
            'prefix' => 'session.',
            'ttl' => null,
        ]);

        self::assertTrue($handler->write('id', 'data'));
        self::assertTrue($pool->invalidateTag('session'));
        self::assertSame('', $handler->read('id'));
    }

    public function testCreatesPrefixedRouterWithoutTagging()
    {
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::once())->method('match')->willReturn(['_route' => 'home']);
        $inner->method('getContext')->willReturn(new RequestContext());
        $router = RouterFactory::get(new ArrayCachePool(), $inner, [
            'ttl' => 60,
            'use_tagging' => false,
            'prefix' => 'routes.',
        ]);

        self::assertSame(['_route' => 'home'], $router->match('/'));
        self::assertSame(['_route' => 'home'], $router->match('/'));
    }

    public function testCreatesTaggableRouter()
    {
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::once())->method('match')->willReturn(['_route' => 'home']);
        $inner->method('getContext')->willReturn(new RequestContext());
        $router = RouterFactory::get(new ArrayCachePool(), $inner, [
            'ttl' => 60,
            'use_tagging' => true,
            'prefix' => '',
        ]);

        self::assertSame(['_route' => 'home'], $router->match('/'));
        self::assertSame(['_route' => 'home'], $router->match('/'));
    }
}
