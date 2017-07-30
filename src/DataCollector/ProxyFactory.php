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

/**
 * Generate proxies over your cache pool. This should only be used in development.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ProxyFactory
{
    /**
     * @type string
     */
    private $proxyDirectory;

    /**
     * @param string $proxyDirectory
     */
    public function __construct($proxyDirectory)
    {
        $this->proxyDirectory = $proxyDirectory;
    }

    /**
     * Create a proxy that handles logging better.
     *
     * @param string $class
     * @param string &$proxyFile  where we store the proxy class
     *
     * @return string the name of a much much better class
     */
    public function createProxy($class, &$proxyFile = null)
    {
        $proxyClass = $this->getProxyClass($class);
        $class      = '\\'.rtrim($class, '\\');
        $proxyFile  = $this->proxyDirectory.'/'.$proxyClass.'.php';

        if (class_exists($proxyClass)) {
            return $proxyClass;
        }

        $content = <<<PROXY
<?php

use Cache\\CacheBundle\\DataCollector\\CacheProxy;
use Cache\\CacheBundle\\DataCollector\\TraceableAdapterEvent;
use Psr\\Cache\\CacheItemInterface;

class $proxyClass extends $class implements CacheProxy
{
    private \$__name;
    private \$__calls = [];

    public function getItem(\$key)
    {
        \$event = \$this->start(__FUNCTION__, \$key);
        try {
            \$item = parent::getItem(\$key);
        } finally {
            \$event->end = microtime(true);
        }
        if (\$item->isHit()) {
            ++\$event->hits;
        } else {
            ++\$event->misses;
        }
        \$event->result = \$item->get();

        return \$item;
    }

    public function hasItem(\$key)
    {
        \$event = \$this->start(__FUNCTION__, \$key);
        try {
            \$event->result = parent::hasItem(\$key);
        } finally {
            \$event->end = microtime(true);
        }

        if (!\$event->result) {
            ++\$event->misses;
        }

        return \$event->result;
    }

    public function deleteItem(\$key)
    {
        \$event = \$this->start(__FUNCTION__, \$key);
        try {
            return \$event->result = parent::deleteItem(\$key);
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function save(CacheItemInterface \$item)
    {
        \$event = \$this->start(__FUNCTION__, \$item);
        try {
            return \$event->result = parent::save(\$item);
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function saveDeferred(CacheItemInterface \$item)
    {
        \$event = \$this->start(__FUNCTION__, \$item);
        try {
            return \$event->result = parent::saveDeferred(\$item);
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function getItems(array \$keys = [])
    {
        \$event = \$this->start(__FUNCTION__, \$keys);
        try {
            \$result = parent::getItems(\$keys);
        } finally {
            \$event->end = microtime(true);
        }
        \$f = function () use (\$result, \$event) {
            \$event->result = [];
            foreach (\$result as \$key => \$item) {
                if (\$item->isHit()) {
                    ++\$event->hits;
                } else {
                    ++\$event->misses;
                }
                \$event->result[\$key] = \$item->get();
                yield \$key => \$item;
            }
        };

        return \$f();
    }

    public function clear()
    {
        \$event = \$this->start(__FUNCTION__);
        try {
            return \$event->result = parent::clear();
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function deleteItems(array \$keys)
    {
        \$event = \$this->start(__FUNCTION__, \$keys);
        try {
            return \$event->result = parent::deleteItems(\$keys);
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function commit()
    {
        \$event = \$this->start(__FUNCTION__);
        try {
            return \$event->result = parent::commit();
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function invalidateTag(\$tag)
    {
        \$event = \$this->start(__FUNCTION__, \$tag);
        try {
            return \$event->result = parent::invalidateTag(\$tag);
        } finally {
            \$event->end = microtime(true);
        }
    }

    public function invalidateTags(array \$tags)
    {
        \$event = \$this->start(__FUNCTION__, \$tags);
        try {
            return \$event->result = parent::invalidateTags(\$tags);
        } finally {
            \$event->end = microtime(true);
        }
    }
    
    public function __getCalls()
    {
        return \$this->__calls;
    }
    
    public function __setName(\$name)
    {
        \$this->__name = \$name;
    }
    
    private function start(\$name, \$argument = null)
    {
        \$this->__calls[]   = \$event   = new TraceableAdapterEvent();
        \$event->name     = \$name;
        \$event->argument = \$argument;
        \$event->start    = microtime(true);

        return \$event;
    }
}
PROXY;

        $this->checkProxyDirectory();
        file_put_contents($proxyFile, $content);
        require $proxyFile;

        return $proxyClass;
    }

    private function checkProxyDirectory()
    {
        if (!is_dir($this->proxyDirectory)) {
            @mkdir($this->proxyDirectory, 0777, true);
        }
    }

    private function getProxyClass($namespace)
    {
        return 'php_cache_proxy_'.str_replace('\\', '_', $namespace);
    }
}
