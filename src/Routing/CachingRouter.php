<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Routing;

use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class CachingRouter implements RouterInterface
{
    /**
     * @param array{ttl: int} $config
     */
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly RouterInterface $router,
        private readonly array $config,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function match(string $pathinfo): array
    {
        $cacheItem = $this->getMatchItem($pathinfo);
        if ($cacheItem->isHit()) {
            $result = $cacheItem->get();

            return is_array($result) ? $result : [];
        }

        $result = $this->router->match($pathinfo);
        $cacheItem->set($result)->expiresAfter($this->config['ttl']);
        $this->cache->save($cacheItem);

        return $result;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        $cacheItem = $this->getGenerateItem($name, $parameters, $referenceType);
        if ($cacheItem->isHit()) {
            $result = $cacheItem->get();

            return is_string($result) ? $result : '';
        }

        $result = $this->router->generate($name, $parameters, $referenceType);
        $cacheItem->set($result)->expiresAfter($this->config['ttl']);
        $this->cache->save($cacheItem);

        return $result;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    private function getMatchItem(string $pathinfo): CacheItemInterface
    {
        $context = $this->getContext();
        $key = $this->cacheKey([
            'match',
            $pathinfo,
            $context->getBaseUrl(),
            $context->getMethod(),
            $context->getHost(),
            $context->getScheme(),
            $context->getHttpPort(),
            $context->getHttpsPort(),
            $context->getQueryString(),
            $context->getParameters(),
        ]);

        return $this->getItem($key, 'match');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getGenerateItem(string $name, array $parameters, int $referenceType): CacheItemInterface
    {
        ksort($parameters);
        $context = $this->getContext();
        $key = $this->cacheKey([
            'generate',
            $name,
            $referenceType,
            $parameters,
            $context->getBaseUrl(),
            $context->getPathInfo(),
            $context->getHost(),
            $context->getScheme(),
            $context->getHttpPort(),
            $context->getHttpsPort(),
            $context->getParameters(),
        ]);

        return $this->getItem($key, 'generate');
    }

    /**
     * @param array<mixed> $values
     */
    private function cacheKey(array $values): string
    {
        try {
            $serialized = serialize($values);
        } catch (\Throwable) {
            $serialized = random_bytes(32);
        }

        return hash('sha256', $serialized);
    }

    private function getItem(string $key, string $tag): CacheItemInterface
    {
        $item = $this->cache->getItem($key);

        if ($item instanceof TaggableCacheItemInterface) {
            $item->setTags(['router', $tag]);
        }

        return $item;
    }
}
