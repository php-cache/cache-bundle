memcached-bundle
================

### Memcached Bundle for Symfony 2

Should work in all versions of symfony, and php 5.3

Requires the php5-memcached extension (Works with amazons elasticache extension as well)

### To Install

```sh
	composer.phar require aequasi/memcached-bundle 1.0
```

Add the bundle to app/AppKernerl.php

```php
$bundles(
    ...
       new Aequasi\Bundle\MemcachedBundle\MemcachedBundle(),
    ...
);
```

Then add parameters (probably in parameters.yml) for your servers, and options

```yml
parameters:
    memcached.servers:
        - { host: localhost, port: 11211 }
    memcached.options:
        - { opt: %memcached.const.opt_send_timeout%, value: 3000000 }
```

If you want doctrine to use this as the result and query cache, add this in your orm section
under your entity manager

```yml
doctrine:
    orm:
        entity_managers:
            default:
                query_cache_driver:
                    type: service
                    id: memcached
                result_cache_driver:
                    type: service
                    id: memcached
```

### To Use

You can use the default memcached functions, doctrine's `useResultCache` and `useQueryCache`, or you can use the `cache` function. Heres an example

```php
use Aequasi\Bundle\MemcachedBundle\Service\MemcachedService as Cache;

/** @var $em \Doctrine\ORM\EntityManager */
$data = $this->get( 'memcached' )->cache(
	'somekey',
	function( ) use( $em, $color ) {
		$repo = $em->getRepository( 'AcmeDemoBundle:Fruit' );
		return $repo->findBy( array( 'color' => $color ) );
	}, Cache::THIRTY_MINUTE
);
```

This will attempt to grab `somekey`. If it cant find it, it will run the closure, and cache it for 30 minutes, as `somekey`. You can use a closure here, or a callable, or even just a scalable type.


