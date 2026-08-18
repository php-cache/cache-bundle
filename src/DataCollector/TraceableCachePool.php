<?php

declare(strict_types=1);

namespace Cache\CacheBundle\DataCollector;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class TraceableCachePool implements CacheProxyInterface
{
    /** @var list<TraceableAdapterEvent> */
    private array $calls = [];

    public function __construct(
        private readonly CacheItemPoolInterface $pool,
        private readonly string $name,
    ) {
    }

    public static function create(CacheItemPoolInterface $pool, string $name): self
    {
        if ($pool instanceof TaggableCacheItemPoolInterface) {
            return new TraceableTaggableCachePool($pool, $name);
        }

        return new self($pool, $name);
    }

    public function getItem(string $key): CacheItemInterface
    {
        return $this->traceGetItem($key, fn (): CacheItemInterface => $this->pool->getItem($key));
    }

    /**
     * @param list<string> $keys
     *
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = $this->traceGetItems(
            $keys,
            fn () => $this->pool->getItems($keys),
            CacheItemInterface::class,
        );

        return $this->generateItems($items);
    }

    /**
     * @template TItem of CacheItemInterface
     *
     * @param list<array{string, TItem}> $items
     *
     * @return \Generator<string, TItem>
     */
    protected function generateItems(array $items): \Generator
    {
        foreach ($items as [$key, $item]) {
            yield $key => $item;
        }
    }

    public function hasItem(string $key): bool
    {
        return $this->trace('hasItem', $key, fn (TraceableAdapterEvent $event): bool => $this->pool->hasItem($key));
    }

    public function clear(): bool
    {
        return $this->trace('clear', null, fn (TraceableAdapterEvent $event): bool => $this->pool->clear());
    }

    public function deleteItem(string $key): bool
    {
        return $this->trace('deleteItem', $key, fn (TraceableAdapterEvent $event): bool => $this->pool->deleteItem($key));
    }

    public function deleteItems(array $keys): bool
    {
        return $this->trace('deleteItems', $keys, fn (TraceableAdapterEvent $event): bool => $this->pool->deleteItems($keys));
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->trace('save', $item, fn (TraceableAdapterEvent $event): bool => $this->pool->save($item));
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->trace('saveDeferred', $item, fn (TraceableAdapterEvent $event): bool => $this->pool->saveDeferred($item));
    }

    public function commit(): bool
    {
        return $this->trace('commit', null, fn (TraceableAdapterEvent $event): bool => $this->pool->commit());
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function clearCalls(): void
    {
        $this->calls = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @template TItem of CacheItemInterface
     *
     * @param callable(): TItem $operation
     *
     * @return TItem
     */
    protected function traceGetItem(string $key, callable $operation): CacheItemInterface
    {
        return $this->trace(
            'getItem',
            $key,
            function (TraceableAdapterEvent $event) use ($operation) {
                $item = $operation();
                $event->hits = (int) $item->isHit();
                $event->misses = 1 - $event->hits;

                return $item;
            },
        );
    }

    /**
     * @template TItem of CacheItemInterface
     *
     * @param list<string>                           $keys
     * @param callable(): iterable<array-key, mixed> $operation
     * @param class-string<TItem>                    $itemType
     *
     * @return list<array{string, TItem}>
     */
    protected function traceGetItems(array $keys, callable $operation, string $itemType): array
    {
        return $this->trace(
            'getItems',
            $keys,
            function (TraceableAdapterEvent $event) use ($itemType, $keys, $operation): array {
                $items = [];
                foreach ($operation() as $item) {
                    if (!$item instanceof $itemType) {
                        throw new \UnexpectedValueException(sprintf('Cache pools must return instances of %s.', $itemType));
                    }

                    $items[] = [$item->getKey(), $item];
                    $event->hits += (int) $item->isHit();
                }
                $event->misses = max(0, count($keys) - $event->hits);

                return $items;
            },
        );
    }

    /**
     * @template TResult
     *
     * @param callable(TraceableAdapterEvent): TResult $operation
     *
     * @return TResult
     */
    protected function trace(string $name, mixed $argument, callable $operation): mixed
    {
        $event = $this->start($name, $argument);

        try {
            $result = $operation($event);
        } catch (\Throwable $exception) {
            $this->finish($event, $exception);

            throw $exception;
        }

        $this->finish($event, $result);

        return $result;
    }

    private function start(string $name, mixed $argument): TraceableAdapterEvent
    {
        $event = new TraceableAdapterEvent($name, $argument, microtime(true));
        $this->calls[] = $event;

        return $event;
    }

    private function finish(TraceableAdapterEvent $event, mixed $result): void
    {
        $event->result = $result;
        $event->end = microtime(true);
    }
}
