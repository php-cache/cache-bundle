<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Functional;

use Cache\CacheBundle\CacheBundle;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\CacheBundle\Routing\CachingRouter;
use Cache\SessionHandler\Psr6SessionHandler;
use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\HttpKernel\KernelInterface;

final class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        self::assertInstanceOf(TestKernel::class, $kernel);
        $kernel->addTestBundle(TwigBundle::class);
        $kernel->addTestBundle(WebProfilerBundle::class);
        $kernel->addTestBundle(CacheBundle::class);
        $kernel->addTestConfig(__DIR__.'/config.yml');
        $kernel->handleOptions($options);

        return $kernel;
    }

    #[RunInSeparateProcess]
    public function testBootsWithRouterAndProfilerIntegrations(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertSame(['array_cache'], $container->getParameter('cache.provider_service_ids'));
        self::assertInstanceOf(TraceableCachePool::class, $container->get('array_cache'));
        self::assertInstanceOf(Psr6SessionHandler::class, $container->get('cache.service.session'));
        self::assertInstanceOf(CachingRouter::class, $container->get('cache.service.router'));
        self::assertInstanceOf(CacheDataCollector::class, $container->get('cache.data_collector'));
    }
}
