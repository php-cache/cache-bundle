# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release. 

## 2.2.0

### Added

- Support PHP Cache 3, including `cache/taggable-cache` 3.

## 2.1.0

### Added

- Add `data_collector.include_values`. Set it to `false` to omit call arguments and results from profiler storage and the panel.

### Fixed

- Preserve `PhpCachePool` while profiling so chain providers compile and record each member's hits and misses.

## 2.0.0

### Added

- Support for PHP 8.2 through 8.5, Symfony 6.4 through 8, and PSR Cache 3.
- PHPStan level 9, the Symfony PHP-CS-Fixer rules, and a 100% line coverage gate.
- A typed PSR-6 decorator for profiler tracing.
- Exclusive session locking backed by Symfony Lock, with configurable factory and lease duration.

### Changed

- Require PHP Cache 2 packages.
- Use modern Symfony command, dependency injection, routing, session, and profiler APIs.
- Use the `@Cache/Collector/cache.html.twig` profiler template namespace.

### Removed

- Doctrine, annotation, serializer, and validator integrations.
- Generated subclass proxies and their runtime proxy cache.
- Support for PHP versions below 8.2, Symfony versions below 6.4, and PSR Cache versions below 3.

### Fixed

- Count multi-key reads, deferred writes, and bulk deletes correctly in profiler statistics.
- Configure the cache-backed session handler through Symfony's current session factory services.
- Hold each session lock through reads, writes, timestamp updates, and session ID regeneration.
- Include the complete routing request context in cached match and URL-generation keys.
- Preserve tag support while profiling and trace failed operations and tag invalidations.
- Reset profiler call buffers between requests in long-running workers.
- Resolve private configured providers through a generated service locator in `cache:flush`.

## 1.1.0

### Added

- Support Symfony 4.

### Fixed

- Reset the data collector between requests.
- Load existing profiler proxy classes before reuse.

## 1.0.2

### Fixed

- Fixed inheritence issues with for SF3.3 and above.

## 1.0.1

### Fixed

- Make sure we clone data better in the DataCollector.

## 1.0.0

### Added

- Adds an option to disable the data collector
- Support for SimpleCache

### Changed

- Using stable depedencies
- Added dynamic proxy classes to handle data collection when debugging

## 0.5.0

### Changed

- Using cache/session-handler: ^0.2. **This will break all cached sessions**
- Using cache/taggable-cache: ^0.5 to support the latest versions of the adapters. 
- New Collector and WebProfiler page 

## 0.4.4

### Fixed

- Make sure RecordingPool does not change the type of pool. 

## 0.4.3

### Fixed 

* Require taggable 0.4.3 to avoid bugs in 0.4.2

## 0.4.2

### Added

* A KeyNormalizer that cleans the cache keys from invalid chars.

### Fixed

* Exception when clearing cache with a non taggable pool
* Default value for the second argument to `RecordingCachePool::timeCall` should be array, not null. 

## 0.4.1

No changelog before this version
