<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Factories;

use Quantum\Archive\Exceptions\ArchiveException;
use Quantum\Archive\Adapters\PharAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\Archive\Adapters\ZipAdapter;
use Quantum\Archive\Enums\ArchiveType;
use Quantum\Archive\Archive;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class ArchiveFactory
 * @package Quantum\Archive
 */
class ArchiveFactory
{
    public const ADAPTERS = [
        ArchiveType::PHAR => PharAdapter::class,
        ArchiveType::ZIP => ZipAdapter::class,
    ];

    /**
     * @var array<string, Archive>
     */
    private array $instances = [];

    /**
     * @throws BaseException|ReflectionException
     */
    public static function get(string $type = ArchiveType::PHAR): Archive
    {
        if (!Di::isRegistered(self::class)) {
            Di::register(self::class);
        }

        return Di::get(self::class)->resolve($type);
    }

    /**
     * @throws BaseException
     */
    public function resolve(string $type = ArchiveType::PHAR): Archive
    {
        if (!isset($this->instances[$type])) {
            $this->instances[$type] = $this->createInstance($type);
        }

        return $this->instances[$type];
    }

    /**
     * @throws BaseException
     */
    private function createInstance(string $type): Archive
    {
        if (!isset(self::ADAPTERS[$type])) {
            throw ArchiveException::adapterNotSupported($type);
        }

        $adapterClass = self::ADAPTERS[$type];

        return new Archive(new $adapterClass());
    }
}
