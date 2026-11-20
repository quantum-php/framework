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
     * Path to public debug bar resources
     */
    private string $publicDebugBarFolderPath = 'public/assets/DebugBar/Resources';

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

        if ($fs->exists(assets_dir() . DS . 'DebugBar' . DS . 'Resources' . DS . 'debugbar.css')) {
            $this->error('The debug ber already installed');
            return;
        }

        $this->copyResources($fs, $this->vendorDebugBarFolderPath, $this->publicDebugBarFolderPath);

        $this->info('Debugbar resources successfully published');
    }

    /**
     * Recursively copies the debug bar assets
     * @throws FileSystemException
     */
    private function copyResources(FileSystem $fs, string $src, string $dst): void
    {
        $dir = opendir($src);

        if ($dst != $this->publicDebugBarFolderPath && $fs->makeDirectory($dst) === false) {
            throw FileSystemException::directoryNotWritable($dst);
        }

        if (is_resource($dir)) {
            while (($file = readdir($dir))) {
                if (($file !== '.') && ($file !== '..')) {
                    if ($fs->isDirectory($src . DS . $file)) {
                        $this->copyResources($fs, $src . DS . $file, $dst . DS . $file);
                    } else {
                        copy($src . DS . $file, $dst . DS . $file);
                    }
                }
            }

            closedir($dir);
        }
    }
}
