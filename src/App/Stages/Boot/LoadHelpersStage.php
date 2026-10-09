<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\AppContext;
use Quantum\App\App;

/**
 * Class LoadHelpersStage
 * @package Quantum\App
 */
class LoadHelpersStage implements BootStageInterface
{
    public function process(AppContext $context): void
    {
        $this->loadComponentHelpers();
        $this->loadAppHelpers();
        $this->loadModuleHelpers();
    }

    private function loadComponentHelpers(): void
    {
        $srcDir = dirname(__DIR__, 3);

        $componentDirs = glob($srcDir . DS . '*', GLOB_ONLYDIR);

        foreach (is_array($componentDirs) ? $componentDirs : [] as $componentDir) {
            $helperPath = $componentDir . DS . 'Helpers';
            if (is_dir($helperPath)) {
                $this->loadHelperFiles($helperPath);
            }
        }
    }

    private function loadAppHelpers(): void
    {
        $this->loadHelperFiles(App::getBaseDir() . DS . 'helpers');
    }

    private function loadModuleHelpers(): void
    {
        $this->loadHelperFiles(App::getBaseDir() . DS . 'modules' . DS . '*' . DS . 'helpers');
    }

    private function loadHelperFiles(string $dir): void
    {
        $files = glob($dir . DS . '*.php');

        if (!is_array($files)) {
            return;
        }

        foreach ($files as $filename) {
            require_once $filename;
        }
    }
}
