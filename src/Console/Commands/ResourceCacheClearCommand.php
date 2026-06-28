<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use Quantum\Console\CliCommand;
use Quantum\Loader\Setup;
use ReflectionException;
use Exception;

/**
 * Class ResourceCacheClearCommand
 * @package Quantum\Console
 */
class ResourceCacheClearCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'cache:clear';

    /**
     * Command description
     */
    protected ?string $description = 'Clears resource cache';

    /**
     * Command help text
     */
    protected ?string $help = 'The command will clear the resource cache';

    /**
     * Command options
     * @var array<int, array<int|string, mixed>>
     */
    protected array $options = [
        ['all', 'all', 'none', ''],
        ['type', 't', 'required', ''],
        ['module', 'm', 'required', ''],
    ];

    /**
     * @var array<int, string>
     */
    protected array $types = ['views', 'asserts'];

    /**
     * @var array<int, string>
     */
    protected array $modules = [];

    protected ?string $type = null;

    protected ?string $module = null;

    protected string $cacheDir = '';

    protected FileSystem $fs;

    /**
     * @throws BaseException|ReflectionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->fs = FileSystemFactory::get();
    }

    public function exec(): void
    {
        try {
            $this->importConfig();
            $this->initModule($this->getOption('module'));
            $this->initType($this->getOption('type'));
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        if (!$this->fs->isDirectory($this->cacheDir)) {
            $this->error('Cache directory does not exist or is not accessible.');
            return;
        }

        if ($this->module || $this->type) {
            $this->clearResourceModuleAndType($this->module, $this->type);
        } elseif (!empty($this->getOption('all'))) {
            $this->removeFilesFromDirectory($this->cacheDir);
        } else {
            $this->error('Please specify at least one of the following options: --all, --module=moduleName or --type=typeName!');
            return;
        }

        $this->info('Resource cache cleared successfully.');
    }

    private function importModules(): void
    {
        try {
            if (!config()->has('modules')) {
                config()->import(new Setup('config', 'modules'));
            }

            if (config()->has('modules') && is_array(config()->get('modules.modules'))) {
                $this->modules = array_keys(array_change_key_case(config()->get('modules.modules')));
            }
        } catch (ConfigException|DiException|ReflectionException $e) {
            $this->error($e->getMessage());
            return;
        }
    }

    /**
     * @throws LoaderException|ConfigException|DiException|ReflectionException
     */
    private function importConfig(): void
    {
        if (!config()->has('view_cache')) {
            config()->import(new Setup('config', 'view_cache'));
        }

        $this->cacheDir = base_dir() . DS . config()->get('view_cache.cache_dir', 'cache');
    }

    /**
     * @throws Exception
     */
    private function initModule(?string $moduleOption): void
    {
        if (!in_array($moduleOption, [null, '', '0'], true)) {
            $this->importModules();
            $module = strtolower($moduleOption);

            if (in_array($module, $this->modules)) {
                $this->module = $module;
            } else {
                throw new Exception('Module {' . $module . '} does not exist.');
            }
        }
    }

    /**
     * @throws Exception
     */
    private function initType(?string $typeOption): void
    {
        if (!in_array($typeOption, [null, '', '0'], true)) {
            $type = strtolower($typeOption);

            if (in_array($type, $this->types)) {
                $this->type = $type;
            } else {
                throw new Exception('Cache type {' . $type . '} is invalid.');
            }
        }
    }

    private function clearResourceModuleAndType(?string $moduleName = null, ?string $type = null): void
    {
        $dir = $this->cacheDir;

        if ($type) {
            $dir = $dir . DS . strtolower($type);
        }

        if ($moduleName) {
            if (!$type) {
                $dir = $dir . DS . '*';
            }

            $dir = $dir . DS . strtolower($moduleName);
        }

        $this->removeFilesFromDirectory($dir);
    }

    private function removeFilesFromDirectory(string $dir): void
    {
        $entries = $this->fs->glob($dir . DS . '*');

        foreach ($entries as $entry) {
            if ($this->fs->isDirectory($entry)) {
                $this->removeFilesFromDirectory($entry);

                if ($this->fs->fileName($entry) !== config()->get('view_cache.cache_dir', 'cache') &&
                    count($this->fs->glob($entry . DS . '*')) === 0
                ) {
                    $this->fs->removeDirectory($entry);
                }
            } else {
                $this->fs->remove($entry);
            }
        }
    }
}
