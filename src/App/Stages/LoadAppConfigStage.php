<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\AppContext;
use Quantum\Loader\Setup;
use ReflectionException;

/**
 * Class LoadAppConfigStage
 * @package Quantum\App
 */
class LoadAppConfigStage implements BootStageInterface
{
    /**
     * @throws LoaderException|ConfigException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if (!config()->has('app')) {
            config()->import(new Setup('config', 'app'));
        }
    }
}
