<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

/**
 * NullLock.
 */
class NoLock extends BaseLockStore
{
    public function acquire(): bool
    {
        return true;
    }

    public function forceRelease(): void
    {
    }

    public function isAcquired(): bool
    {
        return true;
    }

    public function release(): bool
    {
        return true;
    }

    protected function read(): array|false
    {
        return false;
    }

    /**
     * @phan-suppress PhanUnusedProtectedMethodParameter
     */
    protected function write(int|float $until): bool
    {
        return false;
    }
}
