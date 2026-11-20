<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Symfony\Component\Console\Tester\CommandTester;
use Quantum\Console\Commands\DebugBarCommand;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;

class DebugBarCommandTest extends AppTestCase
{
    private DebugBarCommand $command;

    private CommandTester $tester;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new DebugBarCommand();
        $this->tester = new CommandTester($this->command);
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('install:debugbar', $this->command->getName());
        $this->assertSame('Publishes debugbar assets', $this->command->getDescription());
        $this->assertSame('The command will publish debugbar assets', $this->command->getHelp());
    }

    public function testExecShowsErrorWhenAlreadyInstalled(): void
    {
        $assetsPath = assets_dir() . DS . 'DebugBar' . DS . 'Resources';

        mkdir($assetsPath, 0777, true);
        file_put_contents($assetsPath . DS . 'debugbar.css', '/* stub */');

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('already installed', $output);

        @unlink($assetsPath . DS . 'debugbar.css');
        @rmdir($assetsPath);
        @rmdir(assets_dir() . DS . 'DebugBar');
    }

    public function testCopyResourcesRecursivelyCopiesFiles(): void
    {
        $sourceDir = base_dir() . DS . 'var' . DS . 'tmp_debugbar_src_' . uniqid();
        $targetDir = base_dir() . DS . 'var' . DS . 'tmp_debugbar_dst_' . uniqid();
        $nestedSourceDir = $sourceDir . DS . 'nested';

        mkdir($nestedSourceDir, 0777, true);
        mkdir($targetDir, 0777, true);
        file_put_contents($sourceDir . DS . 'root.css', 'root');
        file_put_contents($nestedSourceDir . DS . 'nested.js', 'nested');

        $this->setPrivateProperty($this->command, 'publicDebugBarFolderPath', $targetDir);

        $method = new ReflectionMethod($this->command, 'copyResources');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->fs, $sourceDir, $targetDir);

        $this->assertFileExists($targetDir . DS . 'root.css');
        $this->assertFileExists($targetDir . DS . 'nested' . DS . 'nested.js');

        @unlink($targetDir . DS . 'nested' . DS . 'nested.js');
        @unlink($targetDir . DS . 'root.css');
        @rmdir($targetDir . DS . 'nested');
        @rmdir($targetDir);
        @unlink($nestedSourceDir . DS . 'nested.js');
        @unlink($sourceDir . DS . 'root.css');
        @rmdir($nestedSourceDir);
        @rmdir($sourceDir);
    }

    public function testVendorAssetsPathPointsToTheInstalledDebugBarResources(): void
    {
        $vendorPath = $this->getPrivateProperty($this->command, 'vendorDebugBarFolderPath');

        $this->assertFileExists(dirname(__DIR__, 4) . DS . $vendorPath . DS . 'debugbar.css');
        $this->assertFileExists(dirname(__DIR__, 4) . DS . $vendorPath . DS . 'debugbar.js');
    }
}
