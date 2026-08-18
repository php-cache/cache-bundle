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

namespace Cache\CacheBundle\Tests\Unit\Factory;

use Cache\SessionHandler\SessionLockInterface;

final class RecordingSessionLock implements SessionLockInterface
{
    /** @var list<string> */
    public array $acquired = [];

    /** @var list<string> */
    public array $released = [];

    public function acquire(string $sessionId): bool
    {
        $this->acquired[] = $sessionId;

        return true;
    }

    public function release(string $sessionId): void
    {
        $this->released[] = $sessionId;
    }
}
