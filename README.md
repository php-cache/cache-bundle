PHP-Cache cache-bundle [![Build Status](https://travis-ci.org/php-cache/cache-bundle.png?branch=master)](https://travis-ci.org/php-cache/cache-bundle)
====================

#### Cache Bundle for Symfony 2

Symfony 2 library providing PSR-6 compliant cache services for the user. 
It also lets you cache your sessions and routes.

The respective cache extensions will be required for your project.

Redis uses the php redis extension.

#### Requirements

- PHP >= 5.6, < 7.1
- Symfony >= 2.7, < 4.0 
- [Composer](http://getcomposer.org)

#### To Install

Run the following in your project root, assuming you have composer set up for your project
```sh
composer.phar require cache/cache-bundle
```

Add the bundle to app/AppKernel.php

```php
$bundles(
    ...
       new Cache\CacheBundle\CacheBundle(),
    ...
);

To see all the config options, run `php app/console config:dump-reference cache` to view the config settings


#### Doctrine

This bundle allows you to use its services for Doctrine's caching methods of metadata, result, and query.

If you want doctrine to use this as the result and query cache, add this

```yml
cache:
    doctrine:
        enabled: true
        metadata:
            instance: default
            entity_managers:   [ default ]       # the name of your entity_manager connection
            document_managers: [ default ]       # the name of your document_manager connection
        result:
            instance: default
            entity_managers:   [ default, read ] # you may specify multiple entity_managers
        query:
            instance: default
            entity_managers: [ default ]
```

#### Session

This bundle even allows you to store your session data in one of your cache clusters. To enable:

```yml
cache:
    session:
        enabled: true
        instance: default
        prefix: "session_"
        ttl: 7200
```

#### Router

This bundle also provides router caching, to help speed that section up. To enable:

```yml
cache:
    router:
        enabled: true
        instance: default
```

If you change any of your routes, you will need to clear all of the route_* keys in your cache.


#### To Use

To use this with doctrine's entity manager, just make sure you have `useResultCache` and/or `useQueryCache` set to true. If you want to use the user cache, just grab the service out of the container like so:

```php
// Change default to the name of your instance
$cache = $container->get( 'cache.instance.default' );
// Or
$cache = $container->get( 'cache.default' );
```

Here is an example usage of the service:

```php
$cache = $this->get( 'cache.instance.default' );
$item = $cache->getItem('test');
if ($item->isHit()) {
	var_dump($item->get());
	
	return;
}

$cache->saveItem('test', $em->find('AcmeDemoBundle:User', 1), 3600);
```

### Need Help?

Create an issue if you've found a bug, or ping one of us on twitter: @aequasi or @TobiasNyholm