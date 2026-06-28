<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Contracts;

/**
 * Interface ArchiveInterface
 * @package Quantum\Archive
 */
interface ArchiveInterface
{
    /**
     * Checks for the existence of a file
     */
    public function offsetExists(string $filename): bool;

    /**
     * Makes a new empty directory
     */
    public function addEmptyDir(string $directory): bool;

    /**
     * Adds new file to the archive
     */
    public function addFile(string $filePath, string $entryName): bool;

    /**
     * Adds new file to the archive from string
     */
    public function addFromString(string $entryName, string $content): bool;

    /**
     * Adds multiple files to the archive
     * @param array<string, string> $fileNames
     */
    public function addMultipleFiles(array $fileNames): bool;

    /**
     * Files count in the archive
     */
    public function count(): int;

    /**
     * Extracts the archive
     * @param string|array<string>|null $files
     */
    public function extractTo(string $pathToExtract, $files = null): bool;

    /**
     * Delete the file from the archive
     */
    public function deleteFile(string $filename): bool;

    /**
     * Delete a multiple files
     * @param array<string> $fileNames
     */
    public function deleteMultipleFiles(array $fileNames): bool;
}
