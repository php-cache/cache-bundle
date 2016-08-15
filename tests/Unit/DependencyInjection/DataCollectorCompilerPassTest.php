<?php

namespace Cache\CacheBundle\Tests\Unit\DependencyInjection;

use Cache\CacheBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DataCollectorCompilerPass());
    }

    public function testWithLogger()
    {
        $collector = new Definition();
        $this->setDefinition('cache.data_collector', $collector);

        $this->setParameter('cache.logging', ['logger'=>'foo_logger', 'level'=>'bar']);
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'cache.recorder_factory',
            0,
            new Reference('foo_logger')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'cache.recorder_factory',
            1,
            'bar'
        );
    }

    public function testFactory()
    {
        $collector = new Definition();
        $this->setDefinition('cache.data_collector', $collector);

        $collectedService = new Definition();
        $collectedService->addTag('cache.provider');
        $this->setDefinition('collected_pool', $collectedService);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'cache.data_collector',
            'addInstance',
            array(
                'collected_pool',
                new Reference('collected_pool')
            )
        );

        $this->assertContainerBuilderHasService('collected_pool.inner');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('collected_pool', 'cache.provider');
    }
}
