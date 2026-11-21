<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use Quantum\Console\CliCommand;
use ReflectionException;

/**
 * Class DebugBarAssetsCommand
 * @package Quantum\Console
 */
class DebugBarCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'install:debugbar';

    /**
     * Command description
     */
    protected ?string $description = 'Publishes debugbar assets';

    /**
     * Command help text
     */
    protected ?string $help = 'The command will publish debugbar assets';

    /**
     * Path to vendor debug bar resources
     */
    private string $vendorDebugBarFolderPath = 'vendor/php-debugbar/php-debugbar/resources';

    /**
     * Executes the command and publishes the debug bar assets
     * @throws FileSystemException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function exec(): void
    {
        $fs = FileSystemFactory::get();

        $publicDebugBarFolderPath = assets_dir() . DS . 'DebugBar' . DS . 'Resources';

        $distPath = $publicDebugBarFolderPath . DS . 'dist';

        if ($fs->exists($distPath . DS . 'debugbar.min.js') && $fs->exists($distPath . DS . 'debugbar.min.css')) {
            $this->error('The debug ber already installed');
            return;
        }

        $this->copyResources($fs, $this->vendorDebugBarFolderPath, $publicDebugBarFolderPath);

        $this->info('Debugbar resources successfully published');
    }

    /**
     * Recursively copies the debug bar assets
     * @throws FileSystemException
     */
    private function copyResources(FileSystem $fs, string $src, string $dst): void
    {
        if (!$fs->isDirectory($src)) {
            throw FileSystemException::directoryNotExists($src);
        }

        if (!$fs->isDirectory($dst) && !mkdir($dst, 0777, true)) {
            throw FileSystemException::directoryNotWritable($dst);
        }

        $dir = opendir($src);

        if (is_resource($dir)) {
            while (($file = readdir($dir))) {
                if (($file !== '.') && ($file !== '..')) {
                    if ($fs->isDirectory($src . DS . $file)) {
                        $this->copyResources($fs, $src . DS . $file, $dst . DS . $file);
                    } else {
                        if (!$fs->copy($src . DS . $file, $dst . DS . $file)) {
                            closedir($dir);

                            throw FileSystemException::directoryNotWritable($dst);
                        }
                    }
                }
            }

            closedir($dir);
        }
    }
}
