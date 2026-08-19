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

use Cache\CacheBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultsDisableOptionalIntegrations()
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertFalse($config['session']['enabled']);
        self::assertFalse($config['router']['enabled']);
        self::assertFalse($config['logging']['enabled']);
        self::assertNull($config['data_collector']['enabled']);
        self::assertTrue($config['data_collector']['include_values']);
    }

    public function testProcessesRetainedIntegrationOptions()
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'session' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
                'ttl' => 3600,
            ],
            'router' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
                'use_tagging' => false,
                'prefix' => 'routes.',
            ],
            'logging' => [
                'enabled' => true,
                'logger' => 'logger.cache',
            ],
            'data_collector' => [
                'enabled' => false,
            ],
        ]]);

        self::assertSame(3600, $config['session']['ttl']);
        self::assertSame('session_', $config['session']['prefix']);
        self::assertSame('lock.factory', $config['session']['lock_factory']);
        self::assertSame(300, $config['session']['lock_ttl']);
        self::assertSame(604800, $config['router']['ttl']);
        self::assertFalse($config['router']['use_tagging']);
        self::assertSame('routes.', $config['router']['prefix']);
        self::assertSame('logger.cache', $config['logging']['logger']);
        self::assertFalse($config['data_collector']['enabled']);
    }

    public function testProcessesSessionLockOptions()
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [[
            'session' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
                'lock_factory' => 'lock.session.factory',
                'lock_ttl' => 900,
            ],
        ]]);

        self::assertSame('lock.session.factory', $config['session']['lock_factory']);
        self::assertSame(900, $config['session']['lock_ttl']);
    }

    public function testRejectsNonStringServiceIds()
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'session' => [
                'enabled' => true,
                'service_id' => 42,
            ],
        ]]);
    }

    public function testRejectsNonPositiveSessionLockTtl()
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(), [[
            'session' => [
                'enabled' => true,
                'service_id' => 'cache.pool',
                'lock_ttl' => 0,
            ],
        ]]);
    }
}
