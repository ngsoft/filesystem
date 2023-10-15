<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

interface LockProvider
{
    /**
     * Get a LockStore instance.
     *
     * @param string    $name    Lock name
     * @param float|int $seconds maximum lock duration
     * @param string    $owner   owner, for shared locks
     */
    public function lock(string $name, int|float $seconds = 0, string $owner = ''): LockStore;
}
