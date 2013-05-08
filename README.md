memcached-bundle [![Build Status](https://travis-ci.org/aequasi/memcached-bundle.png?branch=master)](https://travis-ci.org/aequasi/memcached-bundle)
================

### Memcached Bundle for Symfony 2

Creates a service in Symfony 2 that can also be used with doctrines `result_cache_driver` and `query_cache_driver`.

There is also functionality for having a key map stored in mysql. Basically, it stores the keys, the size of the value, how long the lifetime is, and when it should expire.

Should work in all versions of symfony, and php 5.3

Requires the php5-memcached extension (Works with amazons elasticache extension as well)

### Requirements

- PHP 5.3.x or 5.4.x
- php5-memcached 1.x or 2.x (this is the PHP "memcached" extension that uses "libmemcached")
- (Works with amazons elasticache extension as well)

![screenshot](http://www.leaseweblabs.com/wp-content/uploads/2013/03/memcache_debug.png)

### To Install

```sh
	composer.phar require aequasi/memcached-bundle 2.0.0
```

Add the bundle to app/AppKernerl.php

```php
$bundles(
    ...
       new Aequasi\Bundle\MemcachedBundle\MemcachedBundle(),
    ...
);
```

Then add parameters (probably in config.yml) for your servers, and options

```yml
memcached:
    clusters:
        default:
          - { host: localhost, port: 11211 }
```

There are also options that you can specify above. You can get the list of options by running

```php
php app/console config:dump memcached
```

#### Doctrine

This bundle allows you to use its services for Doctrine's caching methods of metadata, result, and query.

If you want doctrine to use this as the result and query cache, add this

```yml
memcached:
    doctrine:
        metadata:
            cluster: default
            entity_manager: default          # the name of your entity_manager connection
            document_manager: default        # the name of your document_manager connection
        result:
            cluster: default
            entity_manager: [default, read]  # you may specify multiple entity_managers
            prefix: "result_"                # you may specify a prefix for the entries
        query:
            cluster: default
            entity_manager: default
```

#### Session

This bundle even allows you to store your session data in one of your memcache clusters. To enable:

```yml
memcached:
    session:
        cluster: default
        prefix: "session_"
        ttl: 7200
```

#### Anti Stampede
Taken from Lws\MemcacheBundle

Let us examine a high traffic website case and see how Memcache behaves:

Your cache is stored for 90 minutes. It takes about 3 second to calculate the cache value and 1 ms second to read from cache the cache value. You have about 5000 requests per second and that the value is cached. You get 5000 requests per second taking about 5000 ms to read the values from cache. You might think that that is not possible since 5000 > 1000, but that depends on the number of worker processes on your web server Let's say it is about 100 workers (under high load) with 75 threads each. Your web requests take about 20 ms each. Whenever the cache invalidates (after 90 minutes), during 3 seconds, there will be 15000 requests getting a cache miss. All the threads getting a miss will start to calculate the cache value (because they don't know the other threads are doing the same). This means that during (almost) 3 seconds the server wont answer a single request, but the requests keep coming in. Since each worker has 75 threads (holding 100 x 75 connections), the amount of workers has to go up to be able to process them.

The heavy forking will cause extra CPU usage and the each worker will use extra RAM. This unexpected increase in RAM and CPU is called the 'dog pile' effect or 'stampeding herd' and is very unwelcome during peek hours on a web service.

There is a solution: we serve the old cache entries while calculating the new value and by using an atomic read and write operation we can make sure only one thread will receive a cache miss when the content is invalidated. The algorithm is implemented in AntiDogPileMemcache class in LswMemcacheBundle. It provides the getAdp() and setAdp() functions that can be used as replacements for the normal get and set.

Please note:

Anti Stampede might not be needed if you have low amount of hits or when calculating the new value goes relatively fast.
Anti Stampede might not be needed if you can break up the big calculation into smaller, maybe even with different timeouts for each part.
Anti Stampede might get you older data than the invalidation that is specified. Especially when a thread/worker gets "false" for "get" request, but fails to "set" the new calculated value afterwards.
Anti Stampede's "getAdp" and Anti Stampede "setAdp" are more expensive than the normal "get" and "set", slowing down all cache hits.
Anti Stampede does not guarantee that the dog pile will not occur. Restarting Memcache, flushing data or not enough RAM will also get keys evicted and you will run into the problem anyway.

### To Use

You can use the default memcached functions, doctrine's `useResultCache` and `useQueryCache`, or you can use the `cache` function. Heres an example

```php
use Aequasi\Bundle\MemcachedBundle\Service\MemcachedService as Cache;

/** @var $em \Doctrine\ORM\EntityManager */
$data = $this->get( 'memcached.default' )->cache(
	'somekey',
	function( ) use( $em, $color ) {
		$repo = $em->getRepository( 'AcmeDemoBundle:Fruit' );
		return $repo->findBy( array( 'color' => $color ) );
	}, Cache::THIRTY_MINUTE
);
```

This will attempt to grab `somekey`. If it cant find it, it will run the closure, and cache it for 30 minutes, as `somekey`. You can use a closure here, or a callable, or even just a scalable type.

There are also three commands (might add more later), for getting, setting, and deleting items in cache.

```sh

php app/console memcached:get key

php app/console memcached:set key value lifetime=60

php app/console memcached:delete key

```

### Need Help?

Create an issue if you've found a bug,

or email me at aequasi@gmail.com
