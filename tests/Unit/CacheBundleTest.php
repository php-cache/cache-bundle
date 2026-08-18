<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit;

use Cache\CacheBundle\CacheBundle;
use Cache\CacheBundle\DependencyInjection\Compiler\CacheTaggingPass;
use Cache\CacheBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Cache\CacheBundle\DependencyInjection\Compiler\LoggerPass;
use Cache\CacheBundle\DependencyInjection\Compiler\SessionSupportCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CacheBundleTest extends TestCase
{
    public function testRegistersCompilerPasses(): void
    {
        $container = new ContainerBuilder();

        (new CacheBundle())->build($container);

        $passClasses = array_map(get_class(...), $container->getCompilerPassConfig()->getPasses());
        self::assertContains(CacheTaggingPass::class, $passClasses);
        self::assertContains(SessionSupportCompilerPass::class, $passClasses);
        self::assertContains(LoggerPass::class, $passClasses);
        self::assertContains(DataCollectorCompilerPass::class, $passClasses);
    }
}
