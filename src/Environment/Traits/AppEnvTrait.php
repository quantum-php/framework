<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment\Traits;

use Quantum\Environment\Enums\Env;

/**
 * Trait AppEnvTrait
 * @package Quantum\Environment
 */
trait AppEnvTrait
{
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
}
