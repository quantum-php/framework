<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment;

use Quantum\Environment\Contracts\EnvironmentInterface;
use Quantum\Environment\Exceptions\EnvException;
use Quantum\Environment\Traits\AppEnvTrait;
use Dotenv\Exception\InvalidFileException;
use Quantum\Environment\Traits\WriteTrait;
use Quantum\Environment\Traits\ReadTrait;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Enums\Env;
use Quantum\App\App;
use Dotenv\Dotenv;

/**
 * Class Environment
 * @package Quantum\Environment
 * @uses Dotenv
 */
class Environment implements EnvironmentInterface
{
    use AppEnvTrait;
    use ReadTrait;
    use WriteTrait;

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
     * @throws EnvException|InvalidFileException|DiException|BaseException
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        [$appEnv, $envFile] = $this->resolveEnvironmentFile();
        $envContent = $this->loadDotenvFile($envFile);

        $this->appEnv = $appEnv;
        $this->envContent = $envContent;
        $this->loaded = true;
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
