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
use Dotenv\Exception\InvalidFileException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Enums\Env;
use ReflectionException;
use Quantum\App\App;
use Dotenv\Dotenv;

/**
 * Trait EnvFileTrait
 * @package Quantum\Environment
 */
trait EnvFileTrait
{
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

            $pattern = '/^' . preg_quote($key . '=' . $this->envContent[$key], '/') . '/m';
            $envFileContent = preg_replace($pattern, $key . '=' . $value, $envFileContent);

            fs()->put($envFilePath, (string) $envFileContent);
        } else {
            fs()->append($envFilePath, PHP_EOL . $key . '=' . $value . PHP_EOL);
        }

        $this->envContent[$key] = $value;
    }

    /**
     * @return array{string, string}
     * @throws BaseException
     */
    private function resolveEnvironmentFile(): array
    {
        $baseDir = App::getBaseDir();

        $filePath = $baseDir . DS . 'shared' . DS . 'config' . DS . 'env.php';

        if (!file_exists($filePath)) {
            throw EnvException::fileNotFound($filePath);
        }

        $envConfig = require $filePath;
        $appEnv = $envConfig['app_env'] ?? Env::PRODUCTION;
        $envFile = $this->getEnvFileName($appEnv);

        if (!file_exists($baseDir . DS . $envFile)) {
            throw EnvException::fileNotFound($envFile);
        }

        return [$appEnv, $envFile];
    }

    /**
     * @return array<string, mixed>
     * @throws InvalidFileException
     */
    private function loadDotenvFile(string $envFile): array
    {
        $loadedVars = Dotenv::createArrayBacked(App::getBaseDir(), $envFile)->load();

        return is_array($loadedVars) ? $loadedVars : [];
    }

    private function getEnvFilePath(): string
    {
        return App::getBaseDir() . DS . $this->getEnvFileName($this->appEnv);
    }

    private function getEnvFileName(string $appEnv): string
    {
        return '.env' . ($appEnv !== Env::PRODUCTION ? ".$appEnv" : '');
    }
}
