<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Factories;

use Quantum\App\Adapters\ConsoleAppAdapter;
use Quantum\App\Exceptions\BaseException;
use Quantum\App\Exceptions\AppException;
use Quantum\App\Adapters\WebAppAdapter;
use Quantum\App\Enums\AppType;
use Quantum\App\AppContext;
use Quantum\Di\DiContainer;
use Quantum\App\App;

/**
 * Class AppFactory
 * @package Quantum\App
 */
class AppFactory
{
    /**
     * Supported adapters
     */
    public const ADAPTERS = [
        AppType::WEB => WebAppAdapter::class,
        AppType::CONSOLE => ConsoleAppAdapter::class,
    ];

    /**
     * @var array<string, App>
     */
    private static array $instances = [];

    /**
     * @throws BaseException
     */
    public static function create(string $type, string $baseDir): App
    {

        if (!isset(self::$instances[$type])) {
            self::$instances[$type] = self::createInstance($type, $baseDir);
        }

        return self::$instances[$type];
    }

    public static function destroy(string $type): void
    {
        unset(self::$instances[$type]);
    }

    /**
     * @throws BaseException
     */
    private static function createInstance(string $type, string $baseDir): App
    {
        if (!isset(self::ADAPTERS[$type])) {
            throw AppException::adapterNotSupported($type);
        }

        $container = new DiContainer();

        $context = new AppContext($baseDir, $container);
        App::setContext($context);

        $adapterClass = self::ADAPTERS[$type];

        return new App(new $adapterClass($context));
    }
}
