<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\CacheBundle\DependencyInjection\Compiler\CacheTaggingPass;
use Cache\CacheBundle\DependencyInjection\Compiler\LoggerPass;
use Cache\CacheBundle\DependencyInjection\Compiler\SessionSupportCompilerPass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CompilerPassesTest extends TestCase
{
    public function testTagsConfiguredCachePools()
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.provider_service_ids', ['cache.pool', 'missing', 42]);
        $container->register('cache.pool', ArrayCachePool::class);

        (new CacheTaggingPass())->process($container);

        self::assertTrue($container->getDefinition('cache.pool')->hasTag('cache.provider'));
    }

    public function testTaggingPassIgnoresMissingOrInvalidParameters()
    {
        $container = new ContainerBuilder();
        (new CacheTaggingPass())->process($container);
        $container->setParameter('cache.provider_service_ids', 'cache.pool');
        (new CacheTaggingPass())->process($container);

        self::assertFalse($container->hasDefinition('cache.pool'));
    }

    public function testAddsConfiguredLoggerToAwarePools()
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.logging', ['enabled' => true, 'logger' => 'logger.cache']);
        $container->register('logger.cache', NullLogger::class);
        $container->register('cache.pool', LoggerAwareArrayCachePool::class)->addTag('cache.provider');

        (new LoggerPass())->process($container);
        $container->getDefinition('cache.pool')->setPublic(true);
        $container->compile();

        $pool = $container->get('cache.pool');
        self::assertInstanceOf(LoggerAwareArrayCachePool::class, $pool);
        self::assertInstanceOf(NullLogger::class, $pool->logger);
    }

    public function testLoggerPassIgnoresMissingConfigurationAndUnawarePools()
    {
        $container = new ContainerBuilder();
        (new LoggerPass())->process($container);
        $container->setParameter('cache.logging', []);
        (new LoggerPass())->process($container);
        $container->setParameter('cache.logging', ['logger' => 'logger.cache']);
        $container->register('cache.pool', TraceableCachePool::class)->addTag('cache.provider');
        (new LoggerPass())->process($container);

        self::assertSame([], $container->getDefinition('cache.pool')->getMethodCalls());
    }

    public function testConfiguresTheSessionHandlerAlias()
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.session', ['enabled' => true]);
        $container->register('session.factory');

        (new SessionSupportCompilerPass())->process($container);

        self::assertSame('cache.service.session', (string) $container->getAlias('session.handler'));
    }

    public function testSessionPassRequiresSymfonySessionSupport()
    {
        $container = new ContainerBuilder();
        (new SessionSupportCompilerPass())->process($container);
        $container->setParameter('cache.session', ['enabled' => true]);

        $this->expectException(\LogicException::class);
        (new SessionSupportCompilerPass())->process($container);
    }
}

final class LoggerAwareArrayCachePool extends ArrayCachePool
{
    public ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
