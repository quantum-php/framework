<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Factories;

use Quantum\Storage\Adapters\GoogleDrive\GoogleDriveFileSystemAdapter;
use Quantum\Storage\Adapters\Dropbox\DropboxFileSystemAdapter;
use Quantum\Storage\Adapters\Local\LocalFileSystemAdapter;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\Adapters\GoogleDrive\GoogleDriveApp;
use Quantum\Storage\Contracts\TokenServiceInterface;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Storage\Adapters\Dropbox\DropboxApp;
use Quantum\Storage\Contracts\CloudAppInterface;
use Quantum\Service\Exceptions\ServiceException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Service\Factories\ServiceFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\Storage\Enums\FileSystemType;
use Quantum\Di\Exceptions\DiException;
use Quantum\HttpClient\HttpClient;
use Quantum\Storage\FileSystem;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class FileSystemFactory
 * @package Quantum\Storage
 */
class FileSystemFactory
{
    public const ADAPTERS = [
        FileSystemType::LOCAL => LocalFileSystemAdapter::class,
        FileSystemType::DROPBOX => DropboxFileSystemAdapter::class,
        FileSystemType::GDRIVE => GoogleDriveFileSystemAdapter::class,
    ];

    public const APPS = [
        FileSystemType::DROPBOX => DropboxApp::class,
        FileSystemType::GDRIVE => GoogleDriveApp::class,
    ];

    /**
     * @var array<string, FileSystem>
     */
    private array $instances = [];

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public static function get(?string $adapter = null): FileSystem
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($adapter);
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    public function resolve(?string $adapter = null): FileSystem
    {
        if (!config()->has('fs')) {
            config()->import(new Setup('config', 'fs'));
        }

        $adapter ??= config()->get('fs.default');

        $adapterClass = $this->getAdapterClass($adapter);

        if (!isset($this->instances[$adapter])) {
            $this->instances[$adapter] = $this->createInstance($adapterClass, $adapter);
        }

        return $this->instances[$adapter];
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function createInstance(string $adapterClass, string $adapter): FileSystem
    {
        $fsAdapter = new $adapterClass($this->createCloudApp($adapter));

        if (!$fsAdapter instanceof FilesystemAdapterInterface) {
            throw FileSystemException::adapterNotSupported($adapter);
        }

        return new FileSystem($fsAdapter);
    }

    /**
     * @throws BaseException
     */
    private function getAdapterClass(string $adapter): string
    {
        if (!array_key_exists($adapter, self::ADAPTERS)) {
            throw FileSystemException::adapterNotSupported($adapter);
        }

        return self::ADAPTERS[$adapter];
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function createCloudApp(string $adapter): ?CloudAppInterface
    {
        if ($adapter === FileSystemType::LOCAL || !isset(self::APPS[$adapter])) {
            return null;
        }

        $cloudAppClass = self::APPS[$adapter];

        return new $cloudAppClass(
            config()->get('fs.' . $adapter . '.params.app_key'),
            config()->get('fs.' . $adapter . '.params.app_secret'),
            $this->createTokenService($adapter),
            new HttpClient()
        );
    }

    /**
     * @throws ServiceException|DiException|BaseException|ReflectionException
     */
    private function createTokenService(string $adapter): TokenServiceInterface
    {
        $serviceClass = (string) config()->get('fs.' . $adapter . '.service');

        /** @var class-string<\Quantum\Service\Service> $serviceClass */
        $tokenService = ServiceFactory::create($serviceClass);

        if (!$tokenService instanceof TokenServiceInterface) {
            throw FileSystemException::notInstanceOf($serviceClass, TokenServiceInterface::class);
        }

        return $tokenService;
    }
}
