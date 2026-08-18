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

namespace Cache\CacheBundle\Tests\Unit\Routing;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\Routing\CachingRouter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class CachingRouterTest extends TestCase
{
    /**
     * @return iterable<string, array{\Closure(RequestContext): RequestContext, \Closure(RequestContext): RequestContext, int}>
     */
    public static function generationContextChanges(): iterable
    {
        yield 'scheme' => [
            static fn (RequestContext $context) => $context->setScheme('http'),
            static fn (RequestContext $context) => $context->setScheme('https'),
            RouterInterface::ABSOLUTE_URL,
        ];
        yield 'host' => [
            static fn (RequestContext $context) => $context->setHost('one.test'),
            static fn (RequestContext $context) => $context->setHost('two.test'),
            RouterInterface::ABSOLUTE_URL,
        ];
        yield 'base URL' => [
            static fn (RequestContext $context) => $context->setBaseUrl('/one'),
            static fn (RequestContext $context) => $context->setBaseUrl('/two'),
            RouterInterface::ABSOLUTE_URL,
        ];
        yield 'HTTP port' => [
            static fn (RequestContext $context) => $context->setScheme('http')->setHttpPort(8080),
            static fn (RequestContext $context) => $context->setHttpPort(8081),
            RouterInterface::ABSOLUTE_URL,
        ];
        yield 'HTTPS port' => [
            static fn (RequestContext $context) => $context->setScheme('https')->setHttpsPort(8443),
            static fn (RequestContext $context) => $context->setHttpsPort(8444),
            RouterInterface::ABSOLUTE_URL,
        ];
        yield 'path info' => [
            static fn (RequestContext $context) => $context->setPathInfo('/one/current'),
            static fn (RequestContext $context) => $context->setPathInfo('/two/current'),
            RouterInterface::RELATIVE_PATH,
        ];
        yield 'parameters' => [
            static fn (RequestContext $context) => $context->setParameters(['_locale' => 'en']),
            static fn (RequestContext $context) => $context->setParameters(['_locale' => 'fr']),
            RouterInterface::ABSOLUTE_PATH,
        ];
    }

    /**
     * @return iterable<string, array{\Closure(RequestContext): RequestContext, \Closure(RequestContext): RequestContext}>
     */
    public static function matchingContextChanges(): iterable
    {
        yield 'scheme' => [
            static fn (RequestContext $context) => $context->setScheme('http'),
            static fn (RequestContext $context) => $context->setScheme('https'),
        ];
        yield 'host' => [
            static fn (RequestContext $context) => $context->setHost('one.test'),
            static fn (RequestContext $context) => $context->setHost('two.test'),
        ];
        yield 'base URL' => [
            static fn (RequestContext $context) => $context->setBaseUrl('/one'),
            static fn (RequestContext $context) => $context->setBaseUrl('/two'),
        ];
        yield 'method' => [
            static fn (RequestContext $context) => $context->setMethod('GET'),
            static fn (RequestContext $context) => $context->setMethod('POST'),
        ];
        yield 'HTTP port' => [
            static fn (RequestContext $context) => $context->setScheme('http')->setHttpPort(8080),
            static fn (RequestContext $context) => $context->setHttpPort(8081),
        ];
        yield 'HTTPS port' => [
            static fn (RequestContext $context) => $context->setScheme('https')->setHttpsPort(8443),
            static fn (RequestContext $context) => $context->setHttpsPort(8444),
        ];
        yield 'query string' => [
            static fn (RequestContext $context) => $context->setQueryString('page=1'),
            static fn (RequestContext $context) => $context->setQueryString('page=2'),
        ];
        yield 'parameters' => [
            static fn (RequestContext $context) => $context->setParameters(['tenant' => 'one']),
            static fn (RequestContext $context) => $context->setParameters(['tenant' => 'two']),
        ];
    }

    public function testCachesMatchedRoutes()
    {
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::once())->method('match')->with('/articles')->willReturn(['_route' => 'articles']);
        $inner->method('getContext')->willReturn(new RequestContext());
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        self::assertSame(['_route' => 'articles'], $router->match('/articles'));
        self::assertSame(['_route' => 'articles'], $router->match('/articles'));
    }

    public function testDoesNotReuseKeysForNonSerializableContexts()
    {
        $context = new RequestContext();
        $context->setParameter('callback', static fn (): null => null);
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::exactly(2))->method('match')->willReturn(['_route' => 'articles']);
        $inner->method('getContext')->willReturn($context);
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        $router->match('/articles');
        $router->match('/articles');
    }

    #[DataProvider('matchingContextChanges')]
    public function testMatchedRoutesDoNotCrossRequestContexts(\Closure $configure, \Closure $change)
    {
        $context = new RequestContext();
        $configure($context);
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::exactly(2))
            ->method('match')
            ->willReturnCallback(static fn (): array => ['context' => self::contextValue($context)]);
        $inner->method('getContext')->willReturn($context);
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        $first = $router->match('/articles');
        $change($context);
        $second = $router->match('/articles');

        self::assertNotSame($first, $second);
    }

    public function testCachesGeneratedUrlsRegardlessOfParameterOrder()
    {
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::once())->method('generate')->willReturn('/article/10?page=2');
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        self::assertSame('/article/10?page=2', $router->generate('article', ['id' => 10, 'page' => 2]));
        self::assertSame('/article/10?page=2', $router->generate('article', ['page' => 2, 'id' => 10]));
    }

    #[DataProvider('generationContextChanges')]
    public function testGeneratedUrlsDoNotCrossRequestContexts(\Closure $configure, \Closure $change, int $referenceType)
    {
        $context = new RequestContext();
        $configure($context);
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::exactly(2))
            ->method('generate')
            ->willReturnCallback(static fn (): string => self::contextValue($context));
        $inner->method('getContext')->willReturn($context);
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        $first = $router->generate('home', [], $referenceType);
        $change($context);
        $second = $router->generate('home', [], $referenceType);

        self::assertNotSame($first, $second);
    }

    public function testDelegatesContextAndRouteCollection()
    {
        $context = new RequestContext();
        $routes = new RouteCollection();
        $inner = $this->createMock(RouterInterface::class);
        $inner->expects(self::once())->method('setContext')->with($context);
        $inner->method('getContext')->willReturn($context);
        $inner->method('getRouteCollection')->willReturn($routes);
        $router = new CachingRouter(new ArrayCachePool(), $inner, ['ttl' => 60]);

        $router->setContext($context);

        self::assertSame($context, $router->getContext());
        self::assertSame($routes, $router->getRouteCollection());
    }

    private static function contextValue(RequestContext $context): string
    {
        return json_encode([
            $context->getBaseUrl(),
            $context->getPathInfo(),
            $context->getMethod(),
            $context->getHost(),
            $context->getScheme(),
            $context->getHttpPort(),
            $context->getHttpsPort(),
            $context->getQueryString(),
            $context->getParameters(),
        ], \JSON_THROW_ON_ERROR);
    }
}
