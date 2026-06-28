<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive;

use Quantum\Archive\Exceptions\ArchiveException;
use Quantum\Archive\Contracts\ArchiveInterface;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Archive
 * @package Quantum\Archive
 * @method void setName(string $archiveName)
 * @method bool offsetExists(string $filename)
 * @method bool addEmptyDir(string $directory)
 * @method bool addFile(string $filePath, string $entryName)
 * @method bool addFromString(string $entryName, string $content)
 * @method bool addMultipleFiles(array<string, string> $fileNames)
 * @method int count()
 * @method bool extractTo(string $pathToExtract, $files = null)
 * @method bool deleteFile(string $filename)
 * @method bool deleteMultipleFiles(array<string> $fileNames)
 */
class Archive
{
    private ArchiveInterface $adapter;

    public function __construct(ArchiveInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): ArchiveInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed>|null $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw ArchiveException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
