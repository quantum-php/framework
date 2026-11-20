<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Quantum\Storage\Exceptions\FileSystemException;
use Symfony\Component\Console\Tester\CommandTester;
use Quantum\Console\Commands\DebugBarCommand;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;

class DebugBarCommandTest extends AppTestCase
{
    private DebugBarCommand $command;

    private CommandTester $tester;

    private string $sourceDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new DebugBarCommand();
        $this->tester = new CommandTester($this->command);

        $this->sourceDir = base_dir() . DS . 'var' . DS . 'tmp_debugbar_vendor_' . uniqid();

        mkdir($this->sourceDir . DS . 'dist', 0777, true);
        file_put_contents($this->sourceDir . DS . 'debugbar.css', '/* 3.x */');
        file_put_contents($this->sourceDir . DS . 'dist' . DS . 'debugbar.min.js', '/* 3.x */');

        $this->setPrivateProperty($this->command, 'vendorDebugBarFolderPath', $this->sourceDir);
    }

    public function tearDown(): void
    {
        $this->removeTree($this->sourceDir);
        $this->removeTree(assets_dir() . DS . 'DebugBar');

        parent::tearDown();
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('install:debugbar', $this->command->getName());
        $this->assertSame('Publishes debugbar assets', $this->command->getDescription());
        $this->assertSame('The command will publish debugbar assets', $this->command->getHelp());
    }

    public function testExecShowsErrorWhenAlreadyInstalled(): void
    {
        $distPath = assets_dir() . DS . 'DebugBar' . DS . 'Resources' . DS . 'dist';

        mkdir($distPath, 0777, true);
        file_put_contents($distPath . DS . 'debugbar.min.js', '/* installed */');

        $this->tester->execute([]);

        $this->assertStringContainsString('already installed', $this->tester->getDisplay());
        $this->assertStringEqualsFile($distPath . DS . 'debugbar.min.js', '/* installed */');
    }

    public function testExecPublishesOverTheFilesOfDebugBar2(): void
    {
        $resourcesPath = assets_dir() . DS . 'DebugBar' . DS . 'Resources';

        mkdir($resourcesPath, 0777, true);
        file_put_contents($resourcesPath . DS . 'debugbar.css', '/* 2.x */');
        file_put_contents($resourcesPath . DS . 'custom_debugbar.css', '/* app */');

        $this->tester->execute([]);

        $this->assertStringContainsString('successfully published', $this->tester->getDisplay());
        $this->assertFileExists($resourcesPath . DS . 'dist' . DS . 'debugbar.min.js');
        $this->assertStringEqualsFile($resourcesPath . DS . 'debugbar.css', '/* 3.x */');
        $this->assertStringEqualsFile($resourcesPath . DS . 'custom_debugbar.css', '/* app */');
    }

    public function testExecCreatesTheMissingResourcesFolder(): void
    {
        $this->tester->execute([]);

        $resourcesPath = assets_dir() . DS . 'DebugBar' . DS . 'Resources';

        $this->assertStringContainsString('successfully published', $this->tester->getDisplay());
        $this->assertFileExists($resourcesPath . DS . 'debugbar.css');
        $this->assertFileExists($resourcesPath . DS . 'dist' . DS . 'debugbar.min.js');
    }

    public function testExecFailsWhenTheVendorFolderIsMissing(): void
    {
        $this->setPrivateProperty($this->command, 'vendorDebugBarFolderPath', $this->sourceDir . DS . 'missing');

        $this->expectException(FileSystemException::class);

        $this->tester->execute([]);
    }

    public function testCopyResourcesRecursivelyCopiesFiles(): void
    {
        $targetDir = base_dir() . DS . 'var' . DS . 'tmp_debugbar_dst_' . uniqid();

        $method = new ReflectionMethod($this->command, 'copyResources');
        $method->setAccessible(true);
        $method->invoke($this->command, $this->fs, $this->sourceDir, $targetDir);

        $this->assertFileExists($targetDir . DS . 'debugbar.css');
        $this->assertFileExists($targetDir . DS . 'dist' . DS . 'debugbar.min.js');

        $this->removeTree($targetDir);
    }

    public function testVendorAssetsPathPointsToTheInstalledDebugBarResources(): void
    {
        $vendorPath = $this->getPrivateProperty(new DebugBarCommand(), 'vendorDebugBarFolderPath');

        $this->assertFileExists(dirname(__DIR__, 4) . DS . $vendorPath . DS . 'debugbar.css');
        $this->assertFileExists(dirname(__DIR__, 4) . DS . $vendorPath . DS . 'dist' . DS . 'debugbar.min.js');
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff(scandir($path), ['.', '..']) as $item) {
            is_dir($path . DS . $item) ? $this->removeTree($path . DS . $item) : unlink($path . DS . $item);
        }

        rmdir($path);
    }
}
