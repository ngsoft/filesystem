<?php

declare(strict_types=1);

namespace NGSOFT\Lock;

use NGSOFT\Filesystem\File;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

class LockFactory
{
    public function __construct(
        protected $rootpath = '',
        protected int|float $seconds = 0,
        protected string $owner = ''
    ) {
        if (empty($rootpath))
        {
            $this->rootpath = sys_get_temp_dir();
        }
    }

    /**
     * Create a Php File Lock.
     */
    public function createFileLock(string $name, int $seconds = 0, string $owner = '', string $rootpath = ''): FileLock
    {
        if (empty($rootpath))
        {
            $rootpath = $this->rootpath;
        }

        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }
        return new FileLock($name, $seconds, $owner, rootpath: $rootpath);
    }

    /**
     * Create a .lock file inside the dame directory as the provided file.
     */
    public function createFileSystemLock(string|File $file, int $seconds, string $owner = ''): FileSystemLock
    {
        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }

        return new FileSystemLock($file instanceof File ? $file : File::create($file), $seconds, $owner);
    }

    /**
     * Create a SQLite Lock.
     */
    public function createSQLiteLock(string $name, int $seconds = 0, string $owner = '', string $dbname = 'sqlocks.db3', string $table = 'locks'): SQLiteLock
    {
        $db = $this->rootpath . DIRECTORY_SEPARATOR . $dbname;

        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }

        return new SQLiteLock($name, $seconds, $db, $owner, table: $table);
    }

    /**
     * Create a NoLock.
     */
    public function createNoLock(string $name, int $seconds = 0, string $owner = ''): NoLock
    {
        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }
        return new NoLock($name, $seconds, $owner);
    }

    /**
     * Create a lock using a PSR-6 Cache.
     */
    public function createCacheLock(CacheItemPoolInterface $cache, string $name, int $seconds = 0, string $owner = ''): CacheLock
    {
        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }
        return new CacheLock($cache, $name, $seconds, $owner);
    }

    /**
     * Create a lock using a PSR-16 Cache.
     */
    public function createSimpleCacheLock(CacheInterface $cache, string $name, int $seconds = 0, string $owner = ''): SimpleCacheLock
    {
        if (0 === $seconds)
        {
            $seconds = $this->seconds;
        }

        return new SimpleCacheLock($cache, $name, $seconds, $owner);
    }
}
