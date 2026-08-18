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

namespace Cache\CacheBundle\Tests\Unit\Session;

use Cache\CacheBundle\Session\SymfonySessionLock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

final class SymfonySessionLockTest extends TestCase
{
    public function testAcquiresEachSessionOnceAndReleasesIt()
    {
        $nativeLock = $this->createMock(SharedLockInterface::class);
        $nativeLock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $nativeLock->expects(self::once())->method('release');

        $factory = $this->createMock(LockFactory::class);
        $factory->expects(self::once())
            ->method('createLock')
            ->with('php-cache-session.'.hash('sha256', 'session-id'), 120)
            ->willReturn($nativeLock);

        $lock = new SymfonySessionLock($factory, 120);

        self::assertTrue($lock->acquire('session-id'));
        self::assertTrue($lock->acquire('session-id'));
        $lock->release('unknown-id');
        $lock->release('session-id');
        $lock->release('session-id');
    }

    public function testDoesNotRetainALockThatCouldNotBeAcquired()
    {
        $failedLock = $this->createMock(SharedLockInterface::class);
        $failedLock->expects(self::once())->method('acquire')->with(true)->willReturn(false);
        $failedLock->expects(self::never())->method('release');

        $acquiredLock = $this->createMock(SharedLockInterface::class);
        $acquiredLock->expects(self::once())->method('acquire')->with(true)->willReturn(true);
        $acquiredLock->expects(self::once())->method('release');

        $factory = $this->createMock(LockFactory::class);
        $factory->expects(self::exactly(2))
            ->method('createLock')
            ->willReturnOnConsecutiveCalls($failedLock, $acquiredLock);

        $lock = new SymfonySessionLock($factory);

        self::assertFalse($lock->acquire('session-id'));
        self::assertTrue($lock->acquire('session-id'));
        $lock->release('session-id');
    }
}
