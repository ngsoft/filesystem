<?php

declare(strict_types=1);

namespace NGSOFT\Filesystem;

/**
 * List all files inside a directory.
 *
 * @return string[]
 */
function list_files(Directory|string $directory, array|string $extensions = [], bool $hidden = false, bool $recursive = false): array
{
    if (is_string($directory))
    {
        $directory = Directory::create($directory);
    }
    $iterator = $recursive ? $directory->allFiles($extensions, $hidden) : $directory->files($extensions, $hidden);
    return array_flip($iterator->toArray());
}

/**
 * List all files inside a directory recursively.
 *
 * @return string[]
 */
function list_files_recursive(Directory|string $directory, array|string $extensions = [], bool $hidden = false): array
{
    return list_files($directory, $extensions, $hidden, true);
}

/**
 * List directories inside a directory.
 *
 * @return string[]
 */
function list_directories(Directory|string $directory, bool $recursive = false): array
{
    if (is_string($directory))
    {
        $directory = Directory::create($directory);
    }
    return array_flip($directory->directories($recursive)->toArray());
}

/**
 * Search for a regular file using pattern (pcre/glob/str_contains).
 */
function search_file(Directory|string $directory, string $pattern): array
{
    if (is_string($directory))
    {
        $directory = Directory::create($directory);
    }
    return $directory->search($pattern)->files()->toArray();
}

/**
 * Require file in context isolation
 * use it in a try/catch block.
 */
function require_file(string $file, array $data = [], bool $once = false): mixed
{
    if ( ! is_file($file))
    {
        return null;
    }

    $closure = static function (array $___data): mixed
    {
        extract($___data);
        unset($___data);
        return func_get_arg(2) ? // $once
            require_once func_get_arg(1) : // $file
            require func_get_arg(1); // $file
    };
    // Warnings will be thrown as ErrorException
    set_error_handler(function ($type, $msg, $file, $line)
    {
        if ( ! (error_reporting() & $type))
        {
            return false;
        }
        throw new \ErrorException($msg, 0, $type, $file, $line);
    });

    try
    {
        return $closure($data, $file, $once);
    } finally
    {
        restore_error_handler();
    }
}

/**
 * Require file once in context isolation.
 */
function require_file_once(string $file, array $data = []): mixed
{
    return require_file($file, $data, true);
}

/**
 * Require multiples files at once.
 *
 * @param iterable|string $files can be an array of files or directories
 * @param array           $data  data to extract to the files
 * @param bool            $once  use require_once
 *
 * @return iterable iterator of file => result
 *
 * @throws \ValueError
 */
function require_all(iterable|string $files, array $data = [], bool $once = false): iterable
{
    if ( ! is_iterable($files))
    {
        $files = [$files];
    }
    $result = [];

    foreach ($files as $file)
    {
        if ( ! is_string($file))
        {
            throw new \ValueError(sprintf('Invalid type %s for requested type string.', \get_debug_type($file)));
        }

        if (array_key_exists($file, $result))
        {
            continue;
        }

        if ( ! file_exists($file))
        {
            $result[$file] = null;
            continue;
        }

        if (is_file($file))
        {
            $result[$file] = require_file($file, $data, $once);
            continue;
        }

        foreach (list_files_recursive($file, 'php') as $path => $_file)
        {
            $result[$_file] = require_file($path, $data, $once);
        }
    }

    return $result;
}

/**
 * Require multiple files at once but only once.
 */
function require_all_once(iterable|string $files, array $data = []): iterable
{
    return require_all($files, $data, true);
}
