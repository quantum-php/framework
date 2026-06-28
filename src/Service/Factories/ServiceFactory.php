<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Service\Factories;

use Quantum\Service\Exceptions\ServiceException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Service\Service;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class ServiceFactory
 * @package Quantum\Service
 */
class ServiceFactory
{
    /**
     * Creates and initiates the service once
     * @template T of Service
     * @param class-string<T> $serviceClass
     * @param array<mixed> $args
     * @return T
     * @throws BaseException
     * @throws DiException
     * @throws ReflectionException
     * @throws ServiceException
     */
    public static function get(string $serviceClass, array $args = []): Service
    {
        self::validate($serviceClass);

        if (!Di::isRegistered($serviceClass)) {
            Di::register($serviceClass);
        }

        return Di::get($serviceClass, $args);
    }

    /**
     * Creates new service instance
     * @template T of Service
     * @param class-string<T> $serviceClass
     * @param array<mixed> $args
     * @return T
     * @throws BaseException
     * @throws DiException
     * @throws ReflectionException
     * @throws ServiceException
     */
    public static function create(string $serviceClass, array $args = []): Service
    {
        self::validate($serviceClass);

        return Di::create($serviceClass, $args);
    }

    /**
     * Validates the service class
     * @throws ServiceException
     * @throws BaseException
     */
    private static function validate(string $serviceClass): void
    {
        if (!class_exists($serviceClass)) {
            throw ServiceException::notFound('Service', $serviceClass);
        }

        if (!is_subclass_of($serviceClass, Service::class)) {
            throw ServiceException::notInstanceOf($serviceClass, Service::class);
        }
    }
}
