<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Factories;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Paginator\Adapters\ModelPaginator;
use Quantum\Paginator\Adapters\ArrayPaginator;
use Quantum\Paginator\Enums\PaginatorType;
use Quantum\App\Exceptions\BaseException;
use Quantum\Paginator\Paginator;

/**
 * Class PaginatorFactory
 * @package Quantum\Paginator
 */
class PaginatorFactory
{
    /**
     * Supported adapters
     */
    public const ADAPTERS = [
        PaginatorType::ARRAY => ArrayPaginator::class,
        PaginatorType::MODEL => ModelPaginator::class,
    ];

    /**
     * Required parameters for each adapter type.
     */
    public const REQUIRED_PARAMS = [
        PaginatorType::ARRAY => ['items'],
        PaginatorType::MODEL => ['model'],
    ];

    /**
     * Default parameters applied to all adapters.
     */
    public const DEFAULT_PARAMS = [
        'perPage' => 10,
        'page' => 1,
    ];

    /**
     * Creates a new paginator instance using the selected adapter type.
     * @param array<string, mixed> $params
     * @throws BaseException
     * @throws PaginatorException
     */
    public static function create(string $type, array $params): Paginator
    {
        if (!isset(self::ADAPTERS[$type])) {
            throw PaginatorException::adapterNotSupported($type);
        }

        self::validateRequiredParams($type, $params);

        $adapterClass = self::ADAPTERS[$type];

        $constructorArgs = self::buildConstructorArgs($type, $params);

        $adapter = new $adapterClass(...$constructorArgs);

        return new Paginator($adapter);
    }

    /**
     * Validates that all required parameters are present.
     * @param string $type
     * @param array<string, mixed> $params
     * @throws PaginatorException
     */
    private static function validateRequiredParams(string $type, array $params): void
    {
        $missing = array_diff(self::REQUIRED_PARAMS[$type], array_keys($params));

        if ($missing !== []) {
            throw PaginatorException::missingRequiredParams($type, $missing[0]);
        }
    }

    /**
     * Builds the list of arguments in the correct order for adapter instantiation.
     * @param array<string, mixed> $params
     * @return array<mixed>
     */
    private static function buildConstructorArgs(string $type, array $params): array
    {
        $params = array_merge(self::DEFAULT_PARAMS, $params);

        $args = [];

        foreach (self::REQUIRED_PARAMS[$type] as $key) {
            $args[] = $params[$key];
        }

        foreach (array_keys(self::DEFAULT_PARAMS) as $key) {
            $args[] = $params[$key];
        }

        return $args;
    }
}
