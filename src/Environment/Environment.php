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
use Quantum\Environment\Traits\EnvFileTrait;
use Dotenv\Exception\InvalidFileException;
use Quantum\Environment\Traits\AppEnvTrait;
use Quantum\Environment\Traits\ValueTrait;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Enums\Env;

/**
 * Class Environment
 * @package Quantum\Environment
 * @uses Dotenv
 */
class Environment implements EnvironmentInterface
{
    use AppEnvTrait;
    use ValueTrait;
    use EnvFileTrait;

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
}
