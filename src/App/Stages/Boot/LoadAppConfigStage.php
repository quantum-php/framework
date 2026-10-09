<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\AppContext;
use Quantum\Config\Setup;
use ReflectionException;

/**
 * Class LoadAppConfigStage
 * @package Quantum\App
 */
class LoadAppConfigStage implements BootStageInterface
{
    public const BEFORE = 'boot.config.before';

    public const AFTER = 'boot.config.after';

    /**
     * @throws ConfigException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if (!config()->has('app')) {
            config()->import(new Setup('config', 'app'));
        }
    }
}
