<?php

declare(strict_types=1);

namespace NGSOFT\Filesystem;

use NGSOFT\Traits\ClassUtils;
use SplFileInfo;
use SplFileObject;

abstract class Filesystem implements \Countable, \Stringable
{
    use ClassUtils;

    public const VERSION = '1.0.0';

    protected ?\SplFileInfo $info;
    protected string $path;

    public function __construct(
        string $path,
    ) {
        if (\blank($path))
        {
            throw new \InvalidArgumentException('Filename is empty.');
        }

        $this->path = static::getAbsolute($path);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function __serialize(): array
    {
        return [$this->path];
    }

    public function __unserialize(array $data): void
    {
        list($this->path) = $data;
    }

    public function __debugInfo(): array
    {
        $result = [
            'path' => $this->path,
        ];

        if ($this->exists())
        {
            $result += [
                'ctime' => date(\DATE_DB, $this->ctime()),
                'mtime' => date(\DATE_DB, $this->mtime()),
            ];
        }
        return $result;
    }

    public static function create(string $path): static
    {
        return new static($path);
    }

    /**
     * Check if file exists.
     */
    abstract public function exists(): bool;

    /**
     * Gets an SplFileInfo object for the file.
     */
    public function getFileInfo(): \SplFileInfo
    {
        return $this->info ??= new \SplFileInfo($this->path);
    }

    /**
     * Gets an SplFileObject object for the file.
     */
    public function openFile(string $mode = 'r'): \SplFileObject
    {
        return $this->getFileInfo()->openFile($mode);
    }

    /**
     * Checks if file basename matches regular expression.
     */
    public function matches(string $pattern, int $limit = 1): array|false
    {
        return \preg_exec($pattern, $this->basename(), $limit);
    }

    /**
     * Checks if file basename contains specific string.
     */
    public function contains(string $needle): bool
    {
        return \str_contains($this->basename(), $needle);
    }

    public function isLink(): bool
    {
        return $this->exists() && is_link($this->path);
    }

    /**
     * Get Realpath.
     */
    public function realpath(): string|false
    {
        clearstatcache(true, $this->path);
        return realpath($this->path);
    }

    /**
     * File Path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Checks if file begins with '.'.
     */
    public function hidden(): bool
    {
        return \str_starts_with($this->basename(), '.');
    }

    /**
     * Move/Rename file.
     *
     * @param string $target  new file
     * @param ?bool  $success True if the operation succeeded
     *
     * @return static a File instance for the target
     */
    public function move(string|self $target, bool &$success = null): static
    {
        if (is_object($target) && ! self::isSelf($target))
        {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot move a %s to a %s.',
                    static::classname(),
                    $target::classname()
                )
            );
        }

        $dest    = static::getAbsolute($target);
        $target  = $target instanceof static ? $target : new static($dest);
        $success = false;

        try
        {
            \set_default_error_handler();

            if ($this->exists())
            {
                $this->createDir(dirname($dest));
                $success = rename($this->path, $dest);
            }
        } catch (\Throwable)
        {
            // cannot move so we merge
            $this->doCopy($target, $success);
            $success = $success && $this->delete();
        } finally
        {
            restore_error_handler();
        }

        return $target;
    }

    /**
     * Can Read.
     */
    public function readable(): bool
    {
        return is_readable($this->path);
    }

    /**
     * Can write.
     */
    public function writable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Changes file mode.
     */
    public function chmod(int $permissions): bool
    {
        return $this->exists() && chmod($this->path, $permissions);
    }

    /**
     * Gets last access time of the file.
     */
    public function atime(): int
    {
        return fileatime($this->path) ?: 0;
    }

    /**
     * Gets the last modified time.
     */
    public function mtime(): int
    {
        return filemtime($this->path) ?: 0;
    }

    /**
     * Gets the inode change time.
     */
    public function ctime(): int
    {
        return filectime($this->path) ?: 0;
    }

    /**
     * File size.
     */
    public function size(): int
    {
        return filesize($this->path) ?: 0;
    }

    /**
     * File name without directory.
     */
    public function basename(): string
    {
        return basename($this->path);
    }

    public function dirname(): string
    {
        return dirname($this->path);
    }

    public function count(): int
    {
        return $this->size();
    }

    /**
     * Checks if path begins with (drive:)[/\].
     */
    protected static function isRelativePath(string $path): bool
    {
        return ! preg_match('#^(?:(?:\w+:)?[\/\\\]+)#', $path);
    }

    /**
     * Get absolute path.
     */
    protected static function getAbsolute(string|self $path)
    {
        $path = (string) $path;
        return normalize_path(static::isRelativePath($path) ? getcwd() . DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Crates a dir fi it does not exists.
     */
    protected static function createDir(string $dirname): void
    {
        $dirname = static::getAbsolute($dirname);

        if ( ! is_dir($dirname) && ! mkdir($dirname, 0777, true))
        {
            throw new \RuntimeException(sprintf('Cannot create directory %s', $dirname));
        }
    }

    /**
     * Inheritance error on self if directly invoking copy().
     */
    abstract protected function doCopy(string|self $target, bool &$success = null): static;

    /**
     * Delete the file.
     */
    abstract protected function delete(): bool;
}
