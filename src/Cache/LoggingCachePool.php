<?php

/*
 * This file is part of php-cache\cache-bundle package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\CacheBundle\Cache;

use Psr\Log\LoggerInterface;

/**
 * Logg all calls to the cache.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LoggingCachePool extends RecordingCachePool
{
    /**
     * @type LoggerInterface
     */
    private $logger;

    /**
     * @type string
     */
    private $name;

    /**
     * @type string
     */
    private $level = 'info';

    /**
     * @param LoggerInterface $logger
     *
     * @return LoggingCachePool
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return LoggingCachePool
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $level
     *
     * @return LoggingCachePool
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @param $call
     */
    protected function addCall($call)
    {
        $data = [
            'name'      => $this->name,
            'method'    => $call->name,
            'arguments' => json_encode($call->arguments),
            'hit'       => isset($call->isHit) ? $call->isHit ? 'True' : 'False' : 'Invalid',
            'time'      => round($call->time * 1000, 2),
            'result'    => $call->result,
        ];

        $this->logger->log(
            $this->level,
            sprintf('[Cache] Provider: %s. Method: %s(%s). Hit: %s. Time: %sms. Result: %s',
                $data['name'],
                $data['method'],
                $data['arguments'],
                $data['hit'],
                $data['time'],
                $data['result']
            ),
            $data
        );
    }
}
