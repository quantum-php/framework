<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Environment;
use Quantum\App\AppContext;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class LoadEnvironmentStage
 * @package Quantum\App
 */
class LoadEnvironmentStage implements BootStageInterface
{
    /**
     * @throws EnvException|DiException|BaseException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        $environment = new Environment();

        $environment->load(new Setup('config', 'env'));

        Di::set(Environment::class, $environment);
    }
}
