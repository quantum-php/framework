<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Module\Exceptions\ModuleException;
use Quantum\Router\Exceptions\RouteException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteCollection;
use Quantum\Module\ModuleLoader;
use Quantum\Router\RouteBuilder;
use Quantum\Storage\FileSystem;
use Quantum\Console\CliCommand;
use ReflectionException;
use OpenApi\Generator;
use Quantum\Di\Di;

/**
 * Class OpenApiCommand
 * @package Quantum\Console
 */
class OpenApiCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'install:openapi';

    /**
     * Command description
     */
    protected ?string $description = 'Generates files for OpenApi UI';

    /**
     * Command arguments
     * @var array<int, array<int|string, mixed>>
     */
    protected array $args = [
        ['module', 'required', 'The module name'],
    ];

    /**
     * Command help text
     */
    protected ?string $help = 'The command will publish OpenApi UI resources';

    /**
     * Path to public debug bar resources
     */
    private string $publicOpenApiFolderPath = 'public/assets/OpenApiUi';

    /**
     * Path to vendor debug bar resources
     */
    private string $vendorOpenApiFolderPath = 'vendor/swagger-api/swagger-ui/dist';

    /**
     * Exclude File Names
     * @var array<int, string>
     */
    private array $excludeFileNames = ['index.html', 'swagger-initializer.js', 'favicon-16x16.png', 'favicon-32x32.png'];

    /**
     * Executes the command and generate Open API specifications
     * @throws ModuleException|RouteException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function exec(): void
    {
        $fs = FileSystemFactory::get();

        if (!Di::isRegistered(ModuleLoader::class)) {
            Di::register(ModuleLoader::class);
        }

        $moduleLoader = Di::get(ModuleLoader::class);

        if (!Di::has(RouteCollection::class)) {
            $builder = new RouteBuilder();

            $routeCollection = $builder->build(
                $moduleLoader->loadModulesRoutes(),
                $moduleLoader->getModuleConfigs()
            );

            Di::set(RouteCollection::class, $routeCollection);
        }

        $module = $this->getArgument('module');

        $modulePath = modules_dir() . DS . $module;

        $routes = $modulePath . DS . 'routes' . DS . 'routes.php';

        if (!$fs->exists(assets_dir() . DS . 'OpenApiUi' . DS . 'index.css')) {
            $this->copyResources();
            $this->info('OpenApi resources successfully published');
        }

        if (!$fs->isDirectory($modulePath)) {
            $this->error('The module `' . ucfirst($module) . '` not found');
            return;
        }

        if (request()->routeGroupExists('openapi', $module) && $fs->exists($modulePath . DS . 'resources' . DS . 'openapi' . DS . 'spec.json')) {
            $this->error('The Open API specifications already installed for `' . ucfirst($module) . '` module');
            return;
        }

        if (!request()->routeGroupExists('openapi', $module)) {
            $routeContent = $fs->get($routes);

            if ($routeContent !== false) {
                $fs->put($routes, str_replace('return function ($route) {', $this->openapiRoutes($module), $routeContent));
            }
        }

        if (!$fs->isDirectory($modulePath . DS . 'resources')) {
            $fs->makeDirectory($modulePath . DS . 'resources');
        }

        if (!$fs->isDirectory($modulePath . DS . 'resources' . DS . 'openapi')) {
            $fs->makeDirectory($modulePath . DS . 'resources' . DS . 'openapi');
        }

        $this->generateOpenapiSpecification($fs, $module);
    }

    /**
     * Copies OpenApi resources
     */
    private function copyResources(): void
    {
        if (!is_dir($this->publicOpenApiFolderPath)) {
            mkdir($this->publicOpenApiFolderPath, 0777, true);
        }

        $dir = opendir($this->vendorOpenApiFolderPath);

        if (is_resource($dir)) {
            while (($file = readdir($dir))) {
                if (($file !== '.') && ($file !== '..') && !in_array($file, $this->excludeFileNames)) {
                    copy($this->vendorOpenApiFolderPath . DS . $file, $this->publicOpenApiFolderPath . DS . $file);
                }
            }

            closedir($dir);
        }
    }

    /**
     * Generates file with OpenApi specifications
     */
    private function generateOpenapiSpecification(FileSystem $fs, string $module): void
    {
        $annotationPath = modules_dir() . DS . $module . DS . 'Controllers' . DS . 'OpenApi' . DS;

        $specPath = modules_dir() . DS . $module . DS . 'resources' . DS . 'openapi' . DS . 'spec.json';

        $openApi = (new Generator())->generate([$annotationPath]);

        if ($openApi === null) {
            $this->error('Failed to generate OpenAPI specification.');
            return;
        }

        $fs->put($specPath, $openApi->toJson());

        $this->info('OpenAPI specification generated successfully.');
    }

    /**
     * Gets the OpenApi routes
     */
    private function openapiRoutes(string $module): string
    {
        return 'return function ($route) {
    $route->group("openapi", function ($route) {
        $route->get("docs", function (): Quantum\Http\Response {
            return response()->html(partial("openApi/openApi"));
        });

        $route->get("spec", function (): Quantum\Http\Response {
            $fs = Quantum\Storage\Factories\FileSystemFactory::get();
            return response()->json($fs->getJson(modules_dir() . DS . "' . $module . '" . DS . "resources" . DS . "openapi" . DS . "spec.json"));
        });
    });' . PHP_EOL;
    }
}
