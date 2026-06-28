<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Contracts;

/**
 * Interface FilesystemAdapterInterface
 * @package Quantum\Storage
 */
interface FilesystemAdapterInterface
{
    /**
     * Makes a new directory
     */
    public function makeDirectory(string $dirname, ?string $parentId = null): bool;

    /**
     * Removes the directory
     */
    public function removeDirectory(string $dirname): bool;

    /**
     * Gets the file content
     * @return string|false
     */
    public function get(string $filename);

    /**
     * Puts the content into the file
     * @param mixed $content
     * @return int|false
     */
    public function put(string $filename, $content, ?string $parentId = null);

    /**
     * Appends the content at the end of the file
     * @param mixed $content
     * @return int|false
     */
    public function append(string $filename, $content);

    /**
     * Renames the file
     */
    public function rename(string $oldName, string $newName): bool;

    /**
     * Copy the file to destination
     */
    public function copy(string $source, string $dest): bool;

    /**
     * File Exists
     */
    public function exists(string $filename): bool;

    /**
     * Gets the file size
     * @return int|false
     */
    public function size(string $filename);

    /**
     * Gets file modification time
     * @return int|false
     */
    public function lastModified(string $filename);

    /**
     * Removes the file
     */
    public function remove(string $filename): bool;

    /**
     * Is File
     */
    public function isFile(string $filename): bool;

    /**
     * Is Directory
     */
    public function isDirectory(string $dirname): bool;

    /**
     * Lists the files inside the directory
     * @return array<string>|bool
     */
    public function listDirectory(string $dirname);

}
