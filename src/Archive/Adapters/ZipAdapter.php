<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Adapters;

use Quantum\Archive\Exceptions\ArchiveException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Archive\Contracts\ArchiveInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Storage\FileSystem;
use ReflectionException;
use ZipArchive;
use Exception;

/**
 * Class ZipAdapter
 * @package Quantum\Archive
 */
class ZipAdapter implements ArchiveInterface
{
    private FileSystem $fs;

    private ?string $archiveName = null;

    private ?ZipArchive $archive = null;

    private bool $requiresReopen = true;

    /**
     * @throws BaseException|ReflectionException
     */
    public function __construct()
    {
        $this->fs = FileSystemFactory::get();
    }

    public function setName(string $archiveName): void
    {
        $this->archiveName = $archiveName;
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function offsetExists(string $filename): bool
    {
        $this->ensureArchiveOpen();

        if (!str_contains($filename, '.')) {
            $filename = rtrim($filename, '/') . '/';
        }

        return $this->archive->locateName($filename) !== false;
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function addEmptyDir(string $directory): bool
    {
        $this->ensureArchiveOpen();

        if (!$this->offsetExists($directory)) {
            return $this->archive->addEmptyDir($directory);
        }

        return false;
    }

    /**
     * @inheritDoc
     * @throws BaseException
     */
    public function addFile(string $filePath, ?string $entryName = null): bool
    {
        $this->ensureArchiveOpen();

        if (!$this->fs->exists($filePath)) {
            throw ArchiveException::fileNotFound($filePath);
        }

        $result = $entryName !== null
            ? $this->archive->addFile($filePath, $entryName)
            : $this->archive->addFile($filePath);
        $this->requiresReopen = true;

        return $result;
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function addFromString(string $entryName, string $content): bool
    {
        $this->ensureArchiveOpen();

        $result = $this->archive->addFromString($entryName, $content);
        $this->requiresReopen = true;

        return $result;
    }

    /**
     * @inheritDoc
     * @param array<string, string> $fileNames
     * @throws ArchiveException
     */
    public function addMultipleFiles(array $fileNames): bool
    {
        $this->ensureArchiveOpen();

        try {
            foreach ($fileNames as $entryName => $filePath) {
                $this->addFile($filePath, $entryName);
            }
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function count(): int
    {
        $this->ensureArchiveOpen();

        return $this->archive->count();
    }

    /**
     * @inheritDoc
     * @param array<string>|null $files
     * @throws ArchiveException
     */
    public function extractTo(string $pathToExtract, $files = null): bool
    {
        $this->ensureArchiveOpen();

        return $this->archive->extractTo($pathToExtract);
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function deleteFile(string $filename): bool
    {
        $this->ensureArchiveOpen();

        if ($this->offsetExists($filename)) {
            $result = $this->archive->deleteName($filename);
            $this->requiresReopen = true;

            return $result;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @param array<string> $fileNames
     * @throws ArchiveException
     */
    public function deleteMultipleFiles(array $fileNames): bool
    {
        $this->ensureArchiveOpen();

        foreach ($fileNames as $entryName) {
            if (!$this->deleteFile($entryName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ArchiveException
     * @phpstan-assert ZipArchive $this->archive
     */
    private function ensureArchiveOpen(): void
    {
        if ($this->requiresReopen || $this->archive === null) {
            $this->openArchive();
        }
    }

    /**
     * @throws ArchiveException
     */
    private function openArchive(): void
    {
        if (empty($this->archiveName)) {
            throw ArchiveException::missingArchiveName();
        }

        if ($this->archive === null) {
            $this->archive = new ZipArchive();
        }

        if ($this->archive->filename) {
            $this->archive->close();
        }

        if ($this->archive->open($this->archiveName, ZipArchive::CREATE) !== true) {
            throw ArchiveException::cantOpen($this->archiveName);
        }

        $this->requiresReopen = false;
    }
}
