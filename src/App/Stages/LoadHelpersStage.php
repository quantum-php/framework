<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\AppContext;
use Quantum\Loader\Loader;
use ReflectionException;
use Quantum\App\App;
use Quantum\Di\Di;

/**
 * Class LoadHelpersStage
 * @package Quantum\App
 */
class LoadHelpersStage implements BootStageInterface
{
    /**
     * @throws DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(Loader::class)) {
            Di::register(Loader::class);
        }

        $loader = Di::get(Loader::class);

        $this->loadComponentHelpers($loader);
        $this->loadAppHelpers($loader);
        $this->loadModuleHelpers($loader);
    }

    private function loadComponentHelpers(Loader $loader): void
    {
        $srcDir = dirname(__DIR__, 2);

        $componentDirs = glob($srcDir . DS . '*', GLOB_ONLYDIR);

        foreach (is_array($componentDirs) ? $componentDirs : [] as $componentDir) {
            $helperPath = $componentDir . DS . 'Helpers';
            if (is_dir($helperPath)) {
                $loader->loadDir($helperPath);
            }
        }
    }

    private function loadAppHelpers(Loader $loader): void
    {
        $loader->loadDir(App::getBaseDir() . DS . 'helpers');
    }

    private function loadModuleHelpers(Loader $loader): void
    {
        $loader->loadDir(App::getBaseDir() . DS . 'modules' . DS . '*' . DS . 'helpers');
    }
}
