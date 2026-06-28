<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages;

use Quantum\Module\Exceptions\ModuleException;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Contracts\BootStageInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteCollection;
use Quantum\Router\RouteBuilder;
use Quantum\Module\ModuleLoader;
use Quantum\App\AppContext;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class LoadModulesStage
 * @package Quantum\App
 */
class LoadModulesStage implements BootStageInterface
{
    /**
     * @throws ModuleException|RouteException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(ModuleLoader::class)) {
            Di::register(ModuleLoader::class);
        }

        $moduleLoader = Di::get(ModuleLoader::class);

        $collection = (new RouteBuilder())->build(
            $moduleLoader->loadModulesRoutes(),
            $moduleLoader->getModuleConfigs()
        );

        if (!Di::has(RouteCollection::class)) {
            Di::set(RouteCollection::class, $collection);
        }
    }
}
