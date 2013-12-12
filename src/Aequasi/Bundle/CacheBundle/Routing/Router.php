<?php
/**
 * @author    Aaron Scherer
 * @date      12/11/13
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html Apache License, Version 2.0
 */

namespace Aequasi\Bundle\CacheBundle\Routing;

use Aequasi\Bundle\CacheBundle\Routing\Matcher\CacheUrlMatcher;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;
use Aequasi\Bundle\CacheBundle\Service\CacheService;

class Router extends BaseRouter
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheService
     */
    protected $cache;

    public function __construct(
        ContainerInterface $container,
        $resource,
        array $options = array(),
        RequestContext $context = null
    ) {
        $this->container = $container;

        $this->initializeCache();

        parent::__construct( $container, $resource, $options, $context );
    }

    private function initializeCache()
    {
        $config = $this->container->getParameter( 'aequasi_cache.router' );
        $instance = $config[ 'instance' ];

        /** @var CacheService $cache */
        $this->cache = $this->container->get( 'aequasi_cache.instance.' . $instance );
    }

    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        $matcher = new CacheUrlMatcher( $this->getRouteCollection(), $this->context );
        $matcher->setCache( $this->cache );

        return $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        $key = 'route_collection';

        if (null === $this->collection) {
            if ($this->cache->contains( $key )) {
                return $this->collection = $this->cache->fetch( $key );
            }

            $this->collection = parent::getRouteCollection();
            $this->cache->save( $key, $this->collection, 60 * 60 * 24 * 7 );
        }

        return $this->collection;
    }
}