<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Enums\Env;
use Quantum\Loader\Loader;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\App\App;
use Dotenv\Dotenv;
use Quantum\Di\Di;

/**
 * Class Environment
 * @package Quantum\Environment
 * @uses Dotenv
 */
class Environment
{
    /**
     * Environment file
     */
    private string $envFile = '.env';

    private bool $isMutable = false;

    /**
     * Loaded env content
     * @var array<string, mixed>
     */
    private array $envContent = [];

    private bool $loaded = false;

    private string $appEnv = Env::PRODUCTION;

    public function setMutable(bool $isMutable): Environment
    {
        $this->isMutable = $isMutable;
        return $this;
    }

    /**
     * Loads environment variables from file
     * @throws EnvException|DiException|BaseException|ReflectionException
     */
    public function load(Setup $setup): void
    {
        if ($this->loaded) {
            return;
        }

        if (!Di::isRegistered(Loader::class)) {
            Di::register(Loader::class);
        }

        $envConfig = Di::get(Loader::class)->setup($setup)->load();

        $appEnv = $envConfig['app_env'] ?? Env::PRODUCTION;

        $this->envFile = '.env' . ($appEnv !== Env::PRODUCTION ? ".$appEnv" : '');

        if (!file_exists($this->getEnvFilePath())) {
            throw EnvException::fileNotFound($this->envFile);
        }

        $this->envContent = $this->loadDotenvFile();

        $this->loaded = true;
        $this->appEnv = $appEnv;
    }

    /**
     * Gets the app current environment
     */
    public function getAppEnv(): string
    {
        return $this->appEnv;
    }

    public function isProduction(): bool
    {
        return $this->appEnv === Env::PRODUCTION;
    }

    public function isStaging(): bool
    {
        return $this->appEnv === Env::STAGING;
    }

    public function isDevelopment(): bool
    {
        return $this->appEnv === Env::DEVELOPMENT;
    }

    public function isTesting(): bool
    {
        return $this->appEnv === Env::TESTING;
    }

    public function isLocal(): bool
    {
        return $this->appEnv === Env::LOCAL;
    }

    /**
     * Gets the environment variable value
     * @param null|mixed $default
     * @return mixed
     * @throws EnvException
     */
    public function getValue(string $key, $default = null)
    {
        if (!$this->loaded) {
            throw EnvException::environmentNotLoaded();
        }

        if (array_key_exists($key, $this->envContent)) {
            return $this->envContent[$key];
        }

        return $default;
    }

    /**
     * Checks if there is a such key
     */
    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->envContent);
    }

    /**
     * Gets the row of .env file by given key
     */
    public function getRow(string $key): ?string
    {
        if (!array_key_exists($key, $this->envContent)) {
            return null;
        }

        return $key . '=' . $this->envContent[$key];
    }

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
                throw EnvException::fileNotFound($this->envFile);
            }

            $pattern = '/^' . preg_quote($key . '=' . $this->envContent[$key], '/') . '/m';
            $envFileContent = preg_replace($pattern, $key . '=' . $value, $envFileContent);

            fs()->put($envFilePath, (string) $envFileContent);
        } else {
            fs()->append($envFilePath, PHP_EOL . $key . '=' . $value . PHP_EOL);
        }

        $this->envContent[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDotenvFile(): array
    {
        $loadedVars = Dotenv::createArrayBacked(App::getBaseDir(), $this->envFile)->load();

        return is_array($loadedVars) ? $loadedVars : [];
    }

    private function getEnvFilePath(): string
    {
        return App::getBaseDir() . DS . $this->envFile;
    }
}
