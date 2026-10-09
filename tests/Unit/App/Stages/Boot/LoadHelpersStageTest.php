<?php

namespace Quantum\Tests\Unit\App\Stages\Boot;

use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\Tests\Unit\AppTestCase;

class LoadHelpersStageTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext();
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testLoadHelpersStageLoadsComponentHelpers(): void
    {
        $stage = new LoadHelpersStage();
        $stage->process($this->context);

        $this->assertTrue(function_exists('config'));
        $this->assertTrue(function_exists('env'));
        $this->assertTrue(function_exists('base_dir'));
        $stage->process($this->context);

        $this->assertTrue(function_exists('config'));
    }

    public function testLoadHelpersStageLoadsAppAndModuleHelpersOnce(): void
    {
        $appDir = PROJECT_ROOT . DS . 'helpers';
        $moduleDir = PROJECT_ROOT . DS . 'modules' . DS . 'Test' . DS . 'helpers';
        $createdAppDir = !is_dir($appDir);
        $createdModuleDir = !is_dir($moduleDir);
        $appFile = $appDir . DS . uniqid('app_helper_') . '.php';
        $moduleFile = $moduleDir . DS . uniqid('module_helper_') . '.php';

        try {
            if ($createdAppDir) {
                $this->assertTrue(mkdir($appDir));
            }
            if ($createdModuleDir) {
                $this->assertTrue(mkdir($moduleDir));
            }

            $this->assertNotFalse(file_put_contents($appFile, '<?php $GLOBALS["appHelperLoads"] = ($GLOBALS["appHelperLoads"] ?? 0) + 1;'));
            $this->assertNotFalse(file_put_contents($moduleFile, '<?php $GLOBALS["moduleHelperLoads"] = ($GLOBALS["moduleHelperLoads"] ?? 0) + 1;'));

            $stage = new LoadHelpersStage();
            $stage->process($this->context);
            $stage->process($this->context);

            $this->assertSame(1, $GLOBALS['appHelperLoads'] ?? 0);
            $this->assertSame(1, $GLOBALS['moduleHelperLoads'] ?? 0);
        } finally {
            if (is_file($appFile)) {
                unlink($appFile);
            }
            if (is_file($moduleFile)) {
                unlink($moduleFile);
            }
            if ($createdAppDir && is_dir($appDir)) {
                rmdir($appDir);
            }
            if ($createdModuleDir && is_dir($moduleDir)) {
                rmdir($moduleDir);
            }
            unset($GLOBALS['appHelperLoads'], $GLOBALS['moduleHelperLoads']);
        }
    }
}
