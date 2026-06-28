<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Module;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Module\Exceptions\ModuleException;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Storage\FileSystem;
use ReflectionException;
use Quantum\App\App;
use Quantum\Di\Di;
use Closure;

/**
 * Class ModuleLoader
 * @package Quantum\Module
 */
class ModuleLoader
{
    /**
     * @var array<string, array<string>>
     */
    private array $moduleDependencies = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $moduleConfigs = [];

    /** @var array<string, Closure> */
    private array $moduleRouteClosures = [];

    private FileSystem $fs;

    /**
     * @throws ModuleException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function __construct()
    {
        $this->fs = FileSystemFactory::get();
        Di::registerDependencies($this->loadModulesDependencies());
    }

    /**
     * @return array<string, Closure>
     * @throws ModuleException|RouteException
     */
    public function loadModulesRoutes(): array
    {
        if (empty($this->moduleConfigs)) {
            $this->loadModuleConfig();
        }

        $modulesRoutes = [];

        foreach ($this->moduleConfigs as $module => $options) {
            if (!$this->isModuleEnabled($options)) {
                continue;
            }

            $modulesRoutes[$module] = $this->getModuleRouteDefinitions($module);
        }

        return $modulesRoutes;
    }

    /**
     * @throws ModuleException|RouteException
     */
    private function getModuleRouteDefinitions(string $module): Closure
    {
        if (isset($this->moduleRouteClosures[$module])) {
            return $this->moduleRouteClosures[$module];
        }

        $moduleRoutesPath = modules_dir() . DS . $module . DS . 'routes' . DS . 'routes.php';

        if (!$this->fs->exists($moduleRoutesPath)) {
            throw ModuleException::moduleRoutesNotFound($module);
        }

        $closure = $this->fs->require($moduleRoutesPath);

        if (!$closure instanceof Closure) {
            throw RouteException::notClosure();
        }

        return $this->moduleRouteClosures[$module] = $closure;
    }

    /**
     * @return array<string, string>
     * @throws ModuleException
     */
    public function loadModulesDependencies(): array
    {
        if (empty($this->moduleConfigs)) {
            $this->loadModuleConfig();
        }

        $modulesDependencies = [];

        foreach ($this->moduleConfigs as $module => $options) {
            if (!$this->isModuleEnabled($options)) {
                continue;
            }

            $modulesDependencies = array_merge($modulesDependencies, $this->getModuleDependencies($module));
        }

        return $modulesDependencies;
    }

    /**
     * @return array<string>
     */
    public function getModuleDependencies(string $module): array
    {
        if (!isset($this->moduleDependencies[$module])) {
            $file = modules_dir() . DS . $module . DS . 'config' . DS . 'dependencies.php';

            if ($this->fs->exists($file)) {
                $deps = $this->fs->require($file);

                $this->moduleDependencies[$module] = is_array($deps) ? $deps : [];
            } else {
                $this->moduleDependencies[$module] = [];
            }
        }

        return $this->moduleDependencies[$module];
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws ModuleException
     */
    public function getModuleConfigs(): array
    {
        if (empty($this->moduleConfigs)) {
            $this->loadModuleConfig();
        }

        return $this->moduleConfigs;
    }

    /**
     * @throws ModuleException
     */
    private function loadModuleConfig(): void
    {
        $configPath = App::getBaseDir() . DS . 'shared' . DS . 'config' . DS . 'modules.php';

        if (!$this->fs->exists($configPath)) {
            throw ModuleException::moduleConfigNotFound();
        }

        $this->moduleConfigs = $this->fs->require($configPath);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function isModuleEnabled(array $options): bool
    {
        return (bool) ($options['enabled'] ?? false);
    }
}
