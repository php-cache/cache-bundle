<?php

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
use Symfony\Component\VarDumper\Caster\CutStub;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal
 */
class CacheDataCollector extends DataCollector
{
    /**
     * @var CacheProxyInterface[]
     */
    private $instances = [];

    /**
     * @var VarCloner
     */
    private $cloner = null;

    /**
     * @param string              $name
     * @param CacheProxyInterface $instance
     */
    public function addInstance($name, CacheProxyInterface $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $empty = ['calls' => [], 'config' => [], 'options' => [], 'statistics' => []];
        $this->data = ['instances' => $empty, 'total' => $empty];
        foreach ($this->instances as $name => $instance) {
            $calls = $instance->__getCalls();
            foreach ($calls as $call) {
                if (isset($call->result)) {
                    $call->result = $this->cloneData($call->result);
                }
                if (isset($call->argument)) {
                    $call->argument = $this->cloneData($call->argument);
                }
            }
            $this->data['instances']['calls'][$name] = $calls;
        }

        $this->data['instances']['statistics'] = $this->calculateStatistics();
        $this->data['total']['statistics'] = $this->calculateTotalStatistics();
    }

    /**
     * To be compatible with many versions of Symfony.
     *
     * @param $var
     */
    private function cloneData($var)
    {
        if (method_exists($this, 'cloneVar')) {
            // Symfony 3.2 or higher
            return $this->cloneVar($var);
        }

        if (null === $this->cloner) {
            $this->cloner = new VarCloner();
            $this->cloner->setMaxItems(-1);
            $this->cloner->addCasters($this->getCasters());
        }

        return $this->cloner->cloneVar($var);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php-cache';
    }

    /**
     * Method returns amount of logged Cache reads: "get" calls.
     *
     * @return array
     */
    public function getStatistics()
    {
        return $this->data['instances']['statistics'];
    }

    /**
     * Method returns the statistic totals.
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->data['total']['statistics'];
    }

    /**
     * Method returns all logged Cache call objects.
     *
     * @return mixed
     */
    public function getCalls()
    {
        return $this->data['instances']['calls'];
    }

    /**
     * @return array
     */
    private function calculateStatistics()
    {
        $statistics = [];
        foreach ($this->data['instances']['calls'] as $name => $calls) {
            $statistics[$name] = [
                'calls' => 0,
                'time' => 0,
                'reads' => 0,
                'writes' => 0,
                'deletes' => 0,
                'hits' => 0,
                'misses' => 0,
            ];
            /** @var TraceableAdapterEvent $call */
            foreach ($calls as $call) {
                $statistics[$name]['calls'] += 1;
                $statistics[$name]['time'] += $call->end - $call->start;
                if ('getItem' === $call->name) {
                    $statistics[$name]['reads'] += 1;
                    if ($call->hits) {
                        $statistics[$name]['hits'] += 1;
                    } else {
                        $statistics[$name]['misses'] += 1;
                    }
                } elseif ('getItems' === $call->name) {
                    $count = $call->hits + $call->misses;
                    $statistics[$name]['reads'] += $count;
                    $statistics[$name]['hits'] += $call->hits;
                    $statistics[$name]['misses'] += $count - $call->misses;
                } elseif ('hasItem' === $call->name) {
                    $statistics[$name]['reads'] += 1;
                    if (false === $call->result) {
                        $statistics[$name]['misses'] += 1;
                    } else {
                        $statistics[$name]['hits'] += 1;
                    }
                } elseif ('save' === $call->name) {
                    $statistics[$name]['writes'] += 1;
                } elseif ('deleteItem' === $call->name) {
                    $statistics[$name]['deletes'] += 1;
                }
            }
            if ($statistics[$name]['reads']) {
                $statistics[$name]['hit_read_ratio'] = round(100 * $statistics[$name]['hits'] / $statistics[$name]['reads'], 2);
            } else {
                $statistics[$name]['hit_read_ratio'] = null;
            }
        }

        return $statistics;
    }

    /**
     * @return array
     */
    private function calculateTotalStatistics()
    {
        $statistics = $this->getStatistics();
        $totals = [
            'calls' => 0,
            'time' => 0,
            'reads' => 0,
            'writes' => 0,
            'deletes' => 0,
            'hits' => 0,
            'misses' => 0,
        ];
        foreach ($statistics as $name => $values) {
            foreach ($totals as $key => $value) {
                $totals[$key] += $statistics[$name][$key];
            }
        }
        if ($totals['reads']) {
            $totals['hit_read_ratio'] = round(100 * $totals['hits'] / $totals['reads'], 2);
        } else {
            $totals['hit_read_ratio'] = null;
        }

        return $totals;
    }

    /**
     * @return callable[] The casters to add to the cloner
     */
    private function getCasters()
    {
        return array(
            '*' => function ($v, array $a, Stub $s, $isNested) {
                if (!$v instanceof Stub) {
                    foreach ($a as $k => $v) {
                        if (is_object($v) && !$v instanceof \DateTimeInterface && !$v instanceof Stub) {
                            $a[$k] = new CutStub($v);
                        }
                    }
                }

                return $a;
            },
        );
    }
}
