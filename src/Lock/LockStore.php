<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

interface LockStore
{
    /**
     * Acquires the lock.
     */
    public function acquire(): bool;

    /**
     * Returns whether or not the lock is acquired.
     */
    public function isAcquired(): bool;

    /**
     * Returns the remaining lifetime in seconds.
     */
    public function getRemainingLifetime(): float|int;

    /**
     * Attempt to acquire the lock.
     */
    public function get(?callable $callback = null): mixed;

    /**
     * Attempt to acquire the lock for the given number of seconds.
     */
    public function block(int|float $seconds, ?callable $callback = null): mixed;

    /**
     * Release the lock.
     *
     * @return bool False if lock not already acquired or not owned
     */
    public function release(): bool;

    /**
     * Returns the current owner of the lock.
     */
    public function getOwner(): string;

    /**
     * Releases this lock in disregard of ownership.
     */
    public function forceRelease(): void;
}
