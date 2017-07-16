<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DataCollector;

use Nyholm\NSA;

/**
 * A factory that decorates another factory to be able to use the proxy cache.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class DecoratingFactory
{
    /**
     * @type ProxyFactory
     */
    private $proxyFactory;

    /**
     * @type mixed cache pool
     */
    private $originalObject;

    /**
     * @param ProxyFactory $proxyFactory
     * @param mixed        $originalObject
     */
    public function __construct(ProxyFactory $proxyFactory, $originalObject)
    {
        $this->proxyFactory   = $proxyFactory;
        $this->originalObject = $originalObject;
    }

    public function create()
    {
        $proxyClass = $this->proxyFactory->createProxy(get_class($this->originalObject));
        $rc         = new \ReflectionClass($proxyClass);
        $pool       = $rc->newInstanceWithoutConstructor();

        // Copy properties from original pool to new
        foreach (NSA::getProperties($this->originalObject) as $property) {
            NSA::setProperty($pool, $property, NSA::getProperty($this->originalObject, $property));
        }

        return $pool;
    }
}
