<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Contracts;

/**
 * Interface LocalFilesystemAdapterInterface
 * @package Quantum\Storage
 */
interface LocalFilesystemAdapterInterface extends FilesystemAdapterInterface
{
    /**
     * Find path names matching a pattern
     * @return array<string>|false
     */
    public function glob(string $pattern, int $flags = 0);

    /**
     * Is Readable
     */
    public function isReadable(string $filename): bool;

    /**
     * Is Writable
     */
    public function isWritable(string $filename): bool;

    /**
     * Gets the content between given lines
     * @return array<string>
     */
    public function getLines(string $filename, int $offset = 0, ?int $length = null): array;

    /**
     * Gets the filename with extension
     */
    public function fileNameWithExtension(string $path): string;

    /**
     * Gets the file name
     */
    public function fileName(string $path): string;

    /**
     * Gets the file extension
     */
    public function extension(string $path): string;

    /**
     * Includes the required file
     * @return mixed
     */
    public function require(string $file, bool $once = false);

    /**
     * Includes a file
     * @return mixed
     */
    public function include(string $file, bool $once = false);
}
