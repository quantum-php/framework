<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Environment;
use Quantum\App\AppContext;
use Quantum\Di\Di;

/**
 * Class LoadEnvironmentStage
 * @package Quantum\App
 */
class LoadEnvironmentStage implements BootStageInterface
{
    public const BEFORE = 'boot.environment.before';

    public const AFTER = 'boot.environment.after';

    /**
     * @throws EnvException|DiException|BaseException
     */
    public function process(AppContext $context): void
    {
        $environment = new Environment();

        $environment->load();

        Di::set(Environment::class, $environment);
    }
}
