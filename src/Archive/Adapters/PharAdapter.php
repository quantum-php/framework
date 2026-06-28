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
use Exception;
use Phar;

/**
 * Class PharAdapter
 * @package Quantum\Archive
 */
class PharAdapter implements ArchiveInterface
{
    private FileSystem $fs;

    private ?string $archiveName = null;

    private ?\Phar $archive = null;

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
     * @throws ArchiveException
     */
    public function removeArchive(): bool
    {
        $this->ensureArchiveOpen();

        try {
            $this->archive = null;
            return Phar::unlinkArchive($this->archiveName);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function offsetExists(string $filename): bool
    {
        $this->ensureArchiveOpen();

        return $this->archive->offsetExists($filename);
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function addEmptyDir(string $directory): bool
    {
        $this->ensureArchiveOpen();

        if (!$this->offsetExists($directory)) {
            $this->archive->addEmptyDir($directory);
            return true;
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

        try {
            $entryName !== null
                ? $this->archive->addFile($filePath, $entryName)
                : $this->archive->addFile($filePath);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function addFromString(string $entryName, string $content): bool
    {
        $this->ensureArchiveOpen();

        try {
            $this->archive->addFromString($entryName, $content);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function addMultipleFiles(array $fileNames): bool
    {
        $this->ensureArchiveOpen();

        try {
            foreach ($fileNames as $fileName => $filePath) {
                $this->archive->addFile($filePath, $fileName);
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
     * @throws ArchiveException
     */
    public function extractTo(string $pathToExtract, $files = null): bool
    {
        $this->ensureArchiveOpen();

        try {
            $this->archive->extractTo($pathToExtract, $files);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function deleteFile(string $filename): bool
    {
        $this->ensureArchiveOpen();

        try {
            $this->archive->delete($filename);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     * @throws ArchiveException
     */
    public function deleteMultipleFiles(array $fileNames): bool
    {
        $this->ensureArchiveOpen();

        try {
            foreach ($fileNames as $fileName) {
                $this->archive->delete($fileName);
            }

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @throws ArchiveException
     * @phpstan-assert Phar $this->archive
     * @phpstan-assert string $this->archiveName
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
            try {
                $this->archive = new Phar($this->archiveName);
            } catch (Exception) {
                throw ArchiveException::cantOpen($this->archiveName);
            }
        }

        $this->requiresReopen = false;
    }
}
