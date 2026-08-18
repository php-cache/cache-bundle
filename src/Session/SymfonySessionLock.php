<?php

declare(strict_types=1);

namespace Cache\CacheBundle\Session;

use Cache\SessionHandler\SessionLockInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

final class SymfonySessionLock implements SessionLockInterface
{
    /** @var array<string, LockInterface> */
    private array $locks = [];

    public function __construct(
        private LockFactory $factory,
        private int $ttl = 300,
    ) {
    }

    public function acquire(string $sessionId): bool
    {
        if (isset($this->locks[$sessionId])) {
            return true;
        }

        $lock = $this->factory->createLock('php-cache-session.'.hash('sha256', $sessionId), $this->ttl);
        if (!$lock->acquire(true)) {
            return false;
        }

        $this->locks[$sessionId] = $lock;

        return true;
    }

    public function release(string $sessionId): void
    {
        if (!isset($this->locks[$sessionId])) {
            return;
        }

        $this->locks[$sessionId]->release();
        unset($this->locks[$sessionId]);
    }
}
