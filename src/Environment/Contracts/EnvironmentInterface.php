<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Contracts;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Config\Exceptions\ConfigException;
use Dotenv\Exception\InvalidFileException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Interface EnvironmentInterface
 * @package Quantum\Environment
 */
interface EnvironmentInterface
{
    public function setMutable(bool $isMutable): EnvironmentInterface;

    /**
     * Loads environment variables from file
     * @throws EnvException|InvalidFileException|DiException|BaseException
     */
    public function load(): void;

    /**
     * Gets the app current environment
     */
    public function getAppEnv(): string;

    public function isProduction(): bool;

    public function isStaging(): bool;

    public function isDevelopment(): bool;

    public function isTesting(): bool;

    public function isLocal(): bool;

    /**
     * Gets the environment variable value
     * @throws EnvException
     */
    public function getValue(string $key, mixed $default = null): mixed;

    /**
     * Gets the environment variable value as a boolean
     * @throws EnvException
     */
    public function getBool(string $key, bool $default = false): bool;

    /**
     * Gets the environment variable value as an integer
     * @throws EnvException
     */
    public function getInt(string $key, int $default = 0): int;

    /**
     * Checks if there is a such key
     */
    public function hasKey(string $key): bool;

    /**
     * Gets the row of .env file by given key
     */
    public function getRow(string $key): ?string;

    /**
     * Creates or updates the row in .env
     * @throws EnvException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function updateRow(string $key, ?string $value): void;
}
