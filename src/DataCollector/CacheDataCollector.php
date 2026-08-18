<?php

declare(strict_types=1);

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

final class CacheDataCollector extends DataCollector
{
    /** @var array<string, CacheProxyInterface> */
    private array $instances = [];

    public function __construct()
    {
        $this->reset();
    }

    public function addInstance(string $name, CacheProxyInterface $instance): void
    {
        $this->instances[$name] = $instance;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $calls = [];
        foreach ($this->instances as $name => $instance) {
            $calls[$name] = $instance->getCalls();
        }

        $statistics = $this->calculateStatistics($calls);
        foreach ($calls as $poolCalls) {
            foreach ($poolCalls as $call) {
                $call->result = $this->cloneVar($call->result);
                $call->argument = $this->cloneVar($call->argument);
            }
        }

        $this->data = [
            'calls' => $calls,
            'statistics' => $statistics,
            'totals' => $this->calculateTotals($statistics),
        ];
    }

    public function reset(): void
    {
        foreach ($this->instances as $instance) {
            $instance->clearCalls();
        }

        $this->data = [
            'calls' => [],
            'statistics' => [],
            'totals' => $this->emptyStatistics(),
        ];
    }

    public function getName(): string
    {
        return 'php-cache';
    }

    /**
     * @return array<string, array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}>
     */
    public function getStatistics(): array
    {
        return $this->data['statistics'];
    }

    /**
     * @return array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}
     */
    public function getTotals(): array
    {
        return $this->data['totals'];
    }

    /**
     * @return array<string, list<TraceableAdapterEvent>>
     */
    public function getCalls(): array
    {
        return $this->data['calls'];
    }

    /**
     * @param array<string, list<TraceableAdapterEvent>> $callsByPool
     *
     * @return array<string, array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}>
     */
    private function calculateStatistics(array $callsByPool): array
    {
        $statistics = [];
        foreach ($callsByPool as $name => $calls) {
            $values = $this->emptyStatistics();
            foreach ($calls as $call) {
                ++$values['calls'];
                $values['time'] += $call->end - $call->start;

                if ('getItem' === $call->name || 'hasItem' === $call->name) {
                    ++$values['reads'];
                    $hit = 'getItem' === $call->name ? $call->hits : (int) (true === $call->result);
                    $values['hits'] += $hit;
                    $values['misses'] += 1 - $hit;
                } elseif ('getItems' === $call->name) {
                    $values['reads'] += $call->hits + $call->misses;
                    $values['hits'] += $call->hits;
                    $values['misses'] += $call->misses;
                } elseif ('save' === $call->name || 'saveDeferred' === $call->name) {
                    ++$values['writes'];
                } elseif ('deleteItem' === $call->name) {
                    ++$values['deletes'];
                } elseif ('deleteItems' === $call->name && \is_array($call->argument)) {
                    $values['deletes'] += \count($call->argument);
                }
            }
            $values['hit_read_ratio'] = $values['reads'] > 0
                ? round(100 * $values['hits'] / $values['reads'], 2)
                : null;
            $statistics[$name] = $values;
        }

        return $statistics;
    }

    /**
     * @param array<string, array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}> $statistics
     *
     * @return array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}
     */
    private function calculateTotals(array $statistics): array
    {
        $totals = $this->emptyStatistics();
        foreach ($statistics as $values) {
            $totals['calls'] += $values['calls'];
            $totals['time'] += $values['time'];
            $totals['reads'] += $values['reads'];
            $totals['writes'] += $values['writes'];
            $totals['deletes'] += $values['deletes'];
            $totals['hits'] += $values['hits'];
            $totals['misses'] += $values['misses'];
        }
        $totals['hit_read_ratio'] = $totals['reads'] > 0
            ? round(100 * $totals['hits'] / $totals['reads'], 2)
            : null;

        return $totals;
    }

    /**
     * @return array{calls: int, time: float, reads: int, writes: int, deletes: int, hits: int, misses: int, hit_read_ratio: float|null}
     */
    private function emptyStatistics(): array
    {
        return [
            'calls' => 0,
            'time' => 0.0,
            'reads' => 0,
            'writes' => 0,
            'deletes' => 0,
            'hits' => 0,
            'misses' => 0,
            'hit_read_ratio' => null,
        ];
    }
}
