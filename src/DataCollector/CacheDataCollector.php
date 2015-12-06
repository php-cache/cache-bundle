<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\DataCollector;

use Cache\CacheBundle\Cache\LoggingCachePool;
use Cache\CacheBundle\Service\CacheService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class CacheDataCollector
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CacheDataCollector extends DataCollector
{
    /**
     * Template name
     *
     * @type string
     */
    const TEMPLATE = 'CacheCacheBundle:Collector:cache.html.twig';

    /**
     * @var LoggingCachePool[]
     */
    private $instances = [];

    /**
     * @param                               $name
     * @param LoggingCachePool              $instance
     */
    public function addInstance($name, LoggingCachePool $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request    $request   A Request instance
     * @param Response   $response  A Response instance
     * @param \Exception $exception An Exception instance
     *
     * @api
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $empty      = ['calls' => [], 'config' => [], 'options' => [], 'statistics' => []];
        $this->data = ['instances' => $empty, 'total' => $empty];
        foreach ($this->instances as $name => $instance) {
            $calls = $instance->getCalls();
            $this->data['instances']['calls'][$name] = $calls;
        }
        $this->data['instances']['statistics'] = $this->calculateStatistics();
        $this->data['total']['statistics']     = $this->calculateTotalStatistics();
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     *
     * @api
     */
    public function getName()
    {
        return 'cache';
    }

    /**
     * Method returns amount of logged Cache reads: "get" calls
     *
     * @return array
     */
    public function getStatistics()
    {
        return $this->data['instances']['statistics'];
    }

    /**
     * Method returns the statistic totals
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->data['total']['statistics'];
    }

    /**
     * Method returns all logged Cache call objects
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
                'calls'   => 0,
                'time'    => 0,
                'reads'   => 0,
                'hits'    => 0,
                'misses'  => 0,
                'writes'  => 0,
                'deletes' => 0
            ];
            foreach ($calls as $call) {
                $statistics[$name]['calls'] += 1;
                $statistics[$name]['time'] += $call->time;
                if ($call->name == 'fetch') {
                    $statistics[$name]['reads'] += 1;
                    if ($call->result !== false) {
                        $statistics[$name]['hits'] += 1;
                    } else {
                        $statistics[$name]['misses'] += 1;
                    }
                } elseif ($call->name == 'contains' && $call->result === false) {
                    $statistics[$name]['reads'] += 1;
                    $statistics[$name]['misses'] += 1;
                } elseif ($call->name == 'save') {
                    $statistics[$name]['writes'] += 1;
                } elseif ($call->name == 'delete') {
                    $statistics[$name]['deletes'] += 1;
                }
            }
            if ($statistics[$name]['reads']) {
                $statistics[$name]['ratio'] = 100 * $statistics[$name]['hits'] / $statistics[$name]['reads'].'%';
            } else {
                $statistics[$name]['ratio'] = 'N/A';
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
        $totals     = ['calls' => 0, 'time' => 0, 'reads' => 0, 'hits' => 0, 'misses' => 0, 'writes' => 0];
        foreach ($statistics as $name => $values) {
            foreach ($totals as $key => $value) {
                $totals[$key] += $statistics[$name][$key];
            }
        }
        if ($totals['reads']) {
            $totals['ratio'] = 100 * $totals['hits'] / $totals['reads'].'%';
        } else {
            $totals['ratio'] = 'N/A';
        }

        return $totals;
    }
}
