<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage;

use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class FileSystem
 * @package Quantum\Storage
 * @method bool makeDirectory(string $dirname, ?string $parentId = null)
 * @method bool removeDirectory(string $dirname)
 * @method string|false get(string $filename)
 * @method int|false put(string $filename, string $content, ?string $parentId = null)
 * @method int|false append(string $filename, string $content)
 * @method bool rename(string $oldName, string $newName)
 * @method bool copy(string $source, string $dest)
 * @method bool exists(string $filename)
 * @method int|false size(string $filename)
 * @method int|false lastModified(string $filename)
 * @method bool remove(string $filename)
 * @method bool isFile(string $filename)
 * @method bool isDirectory(string $dirname)
 * @method string fileName(string $path)
 * @method string fileNameWithExtension(string $path)
 * @method string extension(string $path)
 * @method bool isReadable(string $filename)
 * @method bool isWritable(string $filename)
 * @method array<string>|false listDirectory(string $dirname)
 * @method glob(string $pattern, int $flags = 0)
 * @method mixed require (string $file, bool $once = false)
 * @method mixed include (string $file, bool $once = false)
 */
class FileSystem
{
    private FilesystemAdapterInterface $adapter;

    public function __construct(FilesystemAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): FilesystemAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw FileSystemException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
