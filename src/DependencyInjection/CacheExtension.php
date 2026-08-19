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

namespace Cache\CacheBundle\DependencyInjection;

use Cache\CacheBundle\Command\CacheFlushCommand;
use Cache\CacheBundle\DataCollector\CacheDataCollector;
use Cache\CacheBundle\Factory\RouterFactory;
use Cache\CacheBundle\Factory\SessionHandlerFactory;
use Cache\CacheBundle\Routing\CachingRouter;
use Cache\CacheBundle\Session\SymfonySessionLock;
use Cache\SessionHandler\Psr6SessionHandler;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class CacheExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach (['session', 'router', 'logging'] as $section) {
            if ($config[$section]['enabled']) {
                $container->setParameter('cache.'.$section, $config[$section]);
            }
        }

        $this->registerSession($container, $config['session']);
        $this->registerRouter($container, $config['router']);

        $serviceIds = [];
        $builtInPools = [];
        foreach (['session', 'router'] as $section) {
            if ($config[$section]['enabled']) {
                $serviceIds[] = $config[$section]['service_id'];
                $builtInPools[$section] = $config[$section]['service_id'];
            }
        }
        $container->setParameter('cache.provider_service_ids', array_values(array_unique($serviceIds)));

        $dataCollectorEnabled = $config['data_collector']['enabled']
            ?? (bool) $container->getParameter('kernel.debug');
        if ($dataCollectorEnabled) {
            $container->register('cache.data_collector', CacheDataCollector::class)
                ->setArguments([$config['data_collector']['include_values']])
                ->addTag('data_collector', [
                    'template' => '@Cache/Collector/cache.html.twig',
                    'id' => 'php-cache',
                ]);
        }

        $container->register(CacheFlushCommand::class, CacheFlushCommand::class)
            ->setArguments([new ServiceLocatorArgument(), $builtInPools])
            ->addTag('console.command');
    }

    public function getAlias(): string
    {
        return 'cache';
    }

    /**
     * @param array{enabled: false, use_tagging: bool, prefix: string, ttl: int|null, lock_factory: string, lock_ttl: int}|array{enabled: true, service_id: string, use_tagging: bool, prefix: string, ttl: int|null, lock_factory: string, lock_ttl: int} $config
     */
    private function registerSession(ContainerBuilder $container, array $config): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->register('cache.session_lock', SymfonySessionLock::class)
            ->setArguments([
                new Reference($config['lock_factory']),
                $config['lock_ttl'],
            ]);

        $container->register('cache.service.session', Psr6SessionHandler::class)
            ->setFactory([SessionHandlerFactory::class, 'get'])
            ->setArguments([
                new Reference($config['service_id']),
                new Reference('cache.session_lock'),
                $config,
            ]);
    }

    /**
     * @param array{enabled: false, ttl: int, use_tagging: bool, prefix: string}|array{enabled: true, service_id: string, ttl: int, use_tagging: bool, prefix: string} $config
     */
    private function registerRouter(ContainerBuilder $container, array $config): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->register('cache.service.router', CachingRouter::class)
            ->setFactory([RouterFactory::class, 'get'])
            ->setDecoratedService('router', null, 10)
            ->setArguments([
                new Reference($config['service_id']),
                new Reference('cache.service.router.inner'),
                $config,
            ]);
    }
}
