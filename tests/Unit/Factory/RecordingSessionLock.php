<?php

declare(strict_types=1);

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
