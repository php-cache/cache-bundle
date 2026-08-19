# PHP Cache Bundle

[![CI](https://github.com/php-cache/cache-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/php-cache/cache-bundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/cache/cache-bundle/v/stable)](https://packagist.org/packages/cache/cache-bundle)
[![Total Downloads](https://poser.pugx.org/cache/cache-bundle/downloads)](https://packagist.org/packages/cache/cache-bundle)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

This Symfony bundle connects PSR-6 cache pools to framework services. It supports session storage, route caching, PSR-3 logging, the Symfony profiler, and targeted cache clearing. Use [Adapter Bundle](https://github.com/php-cache/adapter-bundle) when you also need to register cache pool services from configuration.

Version 2 requires PHP 8.2 or newer, Symfony 6.4, 7, or 8, PSR Cache 3, and PHP Cache 2 packages.

## Installation

```bash
composer require cache/cache-bundle:^2.0
```

Symfony Flex may register the bundle automatically. Otherwise, add it to `config/bundles.php`:

```php
<?php

return [
    Cache\CacheBundle\CacheBundle::class => ['all' => true],
];
```

## Configuration

The referenced cache pool must already be registered as a service:

```yaml
# config/packages/cache.yaml
cache:
  session:
    enabled: true
    service_id: cache.provider.app
    ttl: 7200
    lock_factory: lock.factory
    lock_ttl: 300

  router:
    enabled: true
    service_id: cache.provider.app
    ttl: 86400

  logging:
    enabled: true
    logger: monolog.logger.cache

  data_collector:
    enabled: true
    include_values: false
```

Enable Symfony sessions when using the session integration:

```yaml
# config/packages/framework.yaml
framework:
  session: true
```

The session handler acquires an exclusive Symfony lock before reading a session and holds it until the session closes or is destroyed. `lock_ttl` is the maximum expected request duration in seconds. Increase it when a request can keep a session open for longer than five minutes.

Symfony uses a local semaphore or file lock by default. That is sufficient for one application host. When several hosts share the session cache, configure `framework.lock` with a shared store such as Redis so every host contends for the same lock. Set `lock_factory` when the session handler should use a named or custom Symfony lock factory.

The profiler collector is enabled by default in debug mode. Set `cache.data_collector.enabled` explicitly to override that default.

Set `cache.data_collector.include_values` to `false` when cached values are large or sensitive. The collector omits call arguments and results from profiler storage and the panel. It still records operations, timings, hit ratios, and per-pool statistics.

Profiler decoration preserves native tag support. Chain providers keep member-level calls, so the panel shows each member's hits and misses. The collector records failed operations and tag invalidations. It also clears its call buffer between requests in long-running workers.

Clear a configured pool with `bin/console cache:flush`:

```bash
bin/console cache:flush session
bin/console cache:flush router
bin/console cache:flush symfony
bin/console cache:flush provider cache.provider.app
bin/console cache:flush all
```

## Upgrading from 1.x

Version 2 removes the Doctrine, annotation, serializer, and validation integrations. Configure those consumers with their native Symfony or Doctrine cache options instead. The generated subclass profiler proxies are also replaced by a regular PSR-6 decorator.

Session storage now requires Symfony Lock and serializes access to each session ID. Review `lock_ttl` and use a shared lock store before deploying to more than one application host.

PHP Cache 2 changes APCu payloads, Redis and Predis tag indexes, namespaced tag indexes, and hierarchy storage paths. Do not mix version 1 and version 2 workers on an affected store.

Clear a namespaced store when a namespace contains bytes outside `[A-Za-z0-9_.]` or lowercase `_x`. Also clear it when a public key contains `|`, `!`, or lowercase `_x`.

Clear namespaced stores containing tagged or hierarchy items. Clear a prefixed store when its prefix contains bytes outside `[A-Za-z0-9_.]` or lowercase `_x`.

Stop or drain old workers, clear each affected store, and then deploy version 2. Follow the same sequence before rolling back.

See the [full Cache Bundle documentation](https://www.php-cache.com/en/latest/symfony/cache-bundle/) for all options.

## Contributing

Send pull requests to the [GitHub repository](https://github.com/php-cache/cache-bundle). Report problems on the [GitHub issue tracker](https://github.com/php-cache/cache-bundle/issues).
