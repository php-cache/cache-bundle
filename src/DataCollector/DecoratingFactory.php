<?php

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
     * @var ProxyFactory
     */
    private $proxyFactory;

    /**
     * @var mixed cache pool
     */
    private $originalObject;

    /**
     * @param ProxyFactory $proxyFactory
     * @param mixed        $originalObject
     */
    public function __construct(ProxyFactory $proxyFactory, $originalObject)
    {
        $this->proxyFactory = $proxyFactory;
        $this->originalObject = $originalObject;
    }

    public function create()
    {
        $proxyClass = $this->proxyFactory->createProxy(get_class($this->originalObject));
        $rc = new \ReflectionClass($proxyClass);
        $pool = $rc->newInstanceWithoutConstructor();

        // Copy properties from original pool to new
        foreach (NSA::getProperties($this->originalObject) as $property) {
            NSA::setProperty($pool, $property, NSA::getProperty($this->originalObject, $property));
        }

        return $pool;
    }
}
