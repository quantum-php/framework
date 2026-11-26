<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Traits;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Trait WriteTrait
 * @package Quantum\Environment
 */
trait WriteTrait
{
    abstract private function getEnvFilePath(): string;

    abstract private function getEnvFileName(string $appEnv): string;

    /**
     * Creates or updates the row in .env
     * @throws EnvException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function updateRow(string $key, ?string $value): void
    {
        if (!$this->isMutable) {
            throw EnvException::environmentImmutable();
        }

        if (!$this->loaded) {
            throw EnvException::environmentNotLoaded();
        }

        $envFilePath = $this->getEnvFilePath();

        if (array_key_exists($key, $this->envContent)) {
            $envFileContent = fs()->get($envFilePath);

            if (!is_string($envFileContent)) {
                throw EnvException::fileNotFound($this->getEnvFileName($this->appEnv));
            }

            $envFileContent = preg_replace_callback(
                $this->rowPattern($key, $this->envContent[$key]),
                static fn (): string => $key . '=' . $value,
                $envFileContent
            );

            if ($envFileContent === null) {
                throw EnvException::fileNotFound($this->getEnvFileName($this->appEnv));
            }

            fs()->put($envFilePath, $envFileContent);
        } else {
            fs()->append($envFilePath, PHP_EOL . $key . '=' . $value . PHP_EOL);
        }

        $this->envContent[$key] = $value;
    }

    /**
     * Matches the key and its value as they are written in .env: quoted, unquoted or without a value
     * (`KEY`, or `KEY=` as updateRow() writes a null value).
     * A comment after the value is not part of the match.
     */
    private function rowPattern(string $key, mixed $value): string
    {
        $key = preg_quote($key, '/');
        $endOfValue = '(?=[ \t]*(?:#|\r?$))';

        if (!is_string($value)) {
            return '/^' . $key . '=?' . $endOfValue . '/m';
        }

        $value = preg_quote($value, '/');

        return '/^(?:' . $key . '="' . $value . '"|' . $key . "='" . $value . "'|" . $key . '=' . $value . $endOfValue . ')/m';
    }
}
