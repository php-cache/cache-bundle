# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release. 

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
