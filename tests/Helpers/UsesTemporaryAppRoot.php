<?php

namespace Quantum\Tests\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

/**
 * Lets a test class work on its own copy of the test app (PROJECT_ROOT) in the system temp directory,
 * so tests that rename or rewrite files of the test app never change the shared one.
 */
trait UsesTemporaryAppRoot
{
    private ?string $temporaryAppRoot = null;

    /**
     * The folder of the test app the tests boot on: a private copy of PROJECT_ROOT, made on first use
     */
    protected function appBaseDir(): string
    {
        if ($this->temporaryAppRoot === null) {
            $this->temporaryAppRoot = sys_get_temp_dir() . DS . 'quantum-test-app-' . uniqid();
            mkdir($this->temporaryAppRoot);

            foreach ($this->appRootItems(PROJECT_ROOT, RecursiveIteratorIterator::SELF_FIRST) as $path => $item) {
                $copy = $this->temporaryAppRoot . substr($path, strlen(PROJECT_ROOT));
                $item->isDir() ? mkdir($copy) : copy($path, $copy);
            }
        }

        return $this->temporaryAppRoot;
    }

    /**
     * Deletes the copy of the test app; called from tearDown()
     */
    protected function removeTemporaryAppRoot(): void
    {
        if ($this->temporaryAppRoot === null) {
            return;
        }

        foreach ($this->appRootItems($this->temporaryAppRoot, RecursiveIteratorIterator::CHILD_FIRST) as $path => $item) {
            $item->isDir() ? @rmdir($path) : @unlink($path);
        }

        @rmdir($this->temporaryAppRoot);
        $this->temporaryAppRoot = null;
    }

    private function appRootItems(string $directory, int $mode): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), $mode);
    }
}
