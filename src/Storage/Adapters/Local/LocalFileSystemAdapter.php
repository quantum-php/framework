<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Adapters\Local;

use Quantum\Storage\Contracts\LocalFilesystemAdapterInterface;
use Throwable;

/**
 * Class LocalFileSystemAdapter
 * @package Quantum\Storage
 */
class LocalFileSystemAdapter implements LocalFilesystemAdapterInterface
{
    /**
     * @inheritDoc
     */
    public function makeDirectory(string $dirname, ?string $parentId = null): bool
    {
        return mkdir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function removeDirectory(string $dirname): bool
    {
        if (!is_dir($dirname)) {
            return false;
        }

        return rmdir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function get(string $filename): string|false
    {
        return file_get_contents($filename);
    }

    /**
     * Reads and returns the content of a file as JSON.
     * @return false|mixed
     */
    public function getJson(string $filename)
    {
        $content = file_get_contents($filename);

        if (in_array($content, ['', '0', false], true)) {
            return false;
        }

        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : false;
    }

    /**
     * @inheritDoc
     */
    public function put(string $filename, $content, ?string $parentId = null): int|false
    {
        return file_put_contents($filename, $content, LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, $content): int|false
    {
        return file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    public function rename(string $oldName, string $newName): bool
    {
        return rename($oldName, $newName);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $dest): bool
    {
        return copy($source, $dest);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $filename): bool
    {
        return file_exists($filename) && is_file($filename);
    }

    /**
     * @inheritDoc
     */
    public function size(string $filename): int|false
    {
        return filesize($filename);
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $filename): int|false
    {
        return filemtime($filename);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $filename): bool
    {
        return unlink($filename);
    }

    /**
     * @inheritDoc
     */
    public function isFile(string $filename): bool
    {
        return is_file($filename);
    }

    /**
     * @inheritDoc
     */
    public function isDirectory(string $dirname): bool
    {
        return is_dir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function listDirectory(string $dirname): array|false
    {
        $entries = [];

        try {
            foreach (scandir($dirname) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $resolved = realpath($dirname . DS . $item);

                    if ($resolved !== false) {
                        $entries[] = $resolved;
                    }
                }
            }

            return $entries;

        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @return array<string>|false
     */
    public function glob(string $pattern, int $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * @inheritDoc
     */
    public function isReadable(string $filename): bool
    {
        return is_readable($filename);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(string $filename): bool
    {
        return is_writable($filename);
    }

    /**
     * @inheritDoc
     * @return array<string>
     */
    public function getLines(string $filename, int $offset = 0, ?int $length = null): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        if (!$lines) {
            return [];
        }

        if ($offset || $length) {
            $lines = array_slice($lines, $offset, $length ?: count($lines), true);
        }

        return $lines;
    }

    /**
     * @inheritDoc
     */
    public function fileNameWithExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * @inheritDoc
     */
    public function fileName(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * @inheritDoc
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @inheritDoc
     */
    public function require(string $file, bool $once = false)
    {
        if ($once) {
            return require_once $file;
        } else {
            return require $file;
        }
    }

    /**
     * @inheritDoc
     */
    public function include(string $file, bool $once = false)
    {
        if ($once) {
            return include_once $file;
        } else {
            return include $file;
        }
    }
}
