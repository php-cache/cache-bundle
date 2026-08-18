<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Tests\Unit\Command;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\CacheBundle\Command\CacheFlushCommand;
use Cache\CacheBundle\DataCollector\TraceableCachePool;
use Cache\CacheBundle\DependencyInjection\Compiler\CacheTaggingPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CacheFlushCommandTest extends TestCase
{
    public function testClearsAProvider(): void
    {
        $pool = new ArrayCachePool();
        $pool->save($pool->getItem('key')->set('value'));
        $container = new ContainerBuilder();
        $container->set('cache.pool', $pool);

        $status = $this->tester($container)->execute([
            'type' => 'provider',
            'service' => 'cache.pool',
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertFalse($pool->hasItem('key'));
    }

    public function testClearsAPrivateConfiguredProviderAfterCompilation(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.provider_service_ids', ['cache.pool']);
        $container->register('cache.pool.inner', ArrayCachePool::class);
        $container->setAlias('cache.pool', 'cache.pool.inner');
        $container->register(CacheFlushCommand::class, CacheFlushCommand::class)
            ->setArgument(0, new Reference('service_container'))
            ->setPublic(true);

        (new CacheTaggingPass())->process($container);
        $container->compile();

        $command = $container->get(CacheFlushCommand::class);
        self::assertInstanceOf(CacheFlushCommand::class, $command);

        $status = (new CommandTester($command))->execute([
            'type' => 'provider',
            'service' => 'cache.pool',
        ]);

        self::assertSame(Command::SUCCESS, $status);
    }

    public function testOnlyExposesPublicAliasesForTaggedProvidersAfterCompilation(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cache.provider_service_ids', []);
        $container->register('cache.provider.app', ArrayAdapter::class)->addTag('cache.provider');
        $container->setAlias('app.cache', new Alias('cache.provider.app', true));
        $container->setAlias('private.cache', 'cache.provider.app');
        $container->register(CacheFlushCommand::class, CacheFlushCommand::class)
            ->setArgument(0, new Reference('service_container'))
            ->setPublic(true);

        (new CacheTaggingPass())->process($container);
        $container->compile();

        $pool = $container->get('app.cache');
        self::assertInstanceOf(ArrayAdapter::class, $pool);
        $pool->save($pool->getItem('key')->set('value'));
        $command = $container->get(CacheFlushCommand::class);
        self::assertInstanceOf(CacheFlushCommand::class, $command);

        $status = (new CommandTester($command))->execute([
            'type' => 'provider',
            'service' => 'app.cache',
        ]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertFalse($pool->hasItem('key'));
        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([
            'type' => 'provider',
            'service' => 'private.cache',
        ]));
    }

    public function testInvalidatesTheTagForABuiltInIntegration(): void
    {
        $pool = new ArrayCachePool();
        $router = $pool->getItem('router')->set('router')->setTags(['router']);
        $session = $pool->getItem('session')->set('session')->setTags(['session']);
        $pool->save($router);
        $pool->save($session);
        $container = new ContainerBuilder();
        $container->set('cache.pool', $pool);

        $status = $this->tester($container, null, ['router' => 'cache.pool'])->execute(['type' => 'router']);

        self::assertSame(Command::SUCCESS, $status);
        self::assertFalse($pool->hasItem('router'));
        self::assertTrue($pool->hasItem('session'));
    }

    public function testRejectsUnknownTypes(): void
    {
        $tester = $this->tester(new ContainerBuilder());

        $status = $tester->execute(['type' => 'unknown']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('Unknown cache type "unknown"', $tester->getDisplay());
    }

    public function testProviderRequiresAnExistingService(): void
    {
        $tester = $this->tester(new ContainerBuilder());

        $status = $tester->execute(['type' => 'provider']);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('Pass a cache pool service ID', $tester->getDisplay());
    }

    public function testProviderRejectsServicesThatAreNotPools(): void
    {
        $container = new ContainerBuilder();
        $container->set('not_a_pool', new \stdClass());

        $status = $this->tester($container)->execute([
            'type' => 'provider',
            'service' => 'not_a_pool',
        ]);

        self::assertSame(Command::FAILURE, $status);
    }

    public function testClearsANonTaggableBuiltInPool(): void
    {
        $inner = new ArrayCachePool();
        $pool = new TraceableCachePool($inner, 'cache.pool');
        $pool->save($pool->getItem('key')->set('value'));
        $container = new ContainerBuilder();
        $container->set('cache.pool', $pool);

        $status = $this->tester($container, null, ['session' => 'cache.pool'])->execute(['type' => 'session']);

        self::assertSame(Command::SUCCESS, $status);
        self::assertFalse($inner->hasItem('key'));
    }

    public function testFailsForInvalidBuiltInConfiguration(): void
    {
        $container = new ContainerBuilder();
        self::assertSame(
            Command::FAILURE,
            $this->tester($container, null, ['router' => []])->execute(['type' => 'router']),
        );
    }

    public function testMissingBuiltInConfigurationIsAlreadyClear(): void
    {
        self::assertSame(
            Command::SUCCESS,
            $this->tester(new ContainerBuilder())->execute(['type' => 'router']),
        );
    }

    public function testRunsTheSymfonyCacheClearCommand(): void
    {
        $tester = $this->tester(new ContainerBuilder(), new class('cache:clear') extends Command {
            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                $output->writeln('cleared');

                return self::SUCCESS;
            }
        });

        $status = $tester->execute(['type' => 'symfony']);

        self::assertSame(Command::SUCCESS, $status);
        self::assertStringContainsString('cleared', $tester->getDisplay());
    }

    public function testSymfonyClearFailsWhenTheCommandIsUnavailable(): void
    {
        self::assertSame(
            Command::FAILURE,
            $this->tester(new ContainerBuilder())->execute(['type' => 'symfony']),
        );
    }

    public function testDecliningTheInteractivePromptDoesNothing(): void
    {
        $tester = $this->tester(new ContainerBuilder());
        $tester->setInputs(['no']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testConfirmingTheInteractivePromptClearsEverything(): void
    {
        $tester = $this->tester(new ContainerBuilder(), new class('cache:clear') extends Command {
            protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
            {
                return self::SUCCESS;
            }
        });
        $tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testClearAllPropagatesFailures(): void
    {
        $container = new ContainerBuilder();
        self::assertSame(
            Command::FAILURE,
            $this->tester($container, null, ['session' => 'missing'])->execute(['type' => 'all']),
        );
    }

    /**
     * @param array<string, mixed> $builtInPools
     */
    private function tester(ContainerBuilder $container, ?Command $additionalCommand = null, array $builtInPools = []): CommandTester
    {
        $application = new Application();
        $command = new CacheFlushCommand($container, $builtInPools);
        $commands = [$command];
        if (null !== $additionalCommand) {
            $commands[] = $additionalCommand;
        }
        $application->addCommands($commands);

        return new CommandTester($command);
    }
}
