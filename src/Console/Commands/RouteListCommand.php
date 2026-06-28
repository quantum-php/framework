<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Module\Exceptions\ModuleException;
use Quantum\Router\Exceptions\RouteException;
use Symfony\Component\Console\Helper\Table;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteCollection;
use Quantum\Router\RouteBuilder;
use Quantum\Module\ModuleLoader;
use Quantum\Console\CliCommand;
use Quantum\Router\Route;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class RouteListCommand
 * @package Quantum\Console
 */
class RouteListCommand extends CliCommand
{
    /**
     * The console command name.
     */
    protected ?string $name = 'route:list';

    /**
     * The console command description.
     */
    protected ?string $description = 'Display all registered routes';

    /**
     * Command options
     * @var array<int, array<int, string>>
     */
    protected array $options = [
        ['module', 'm', 'optional', 'Filter by module name'],
    ];

    /**
     * Executes the command
     * @throws DiException|ReflectionException
     */
    public function exec(): void
    {
        try {
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

            $routeCollection = Di::get(RouteCollection::class);

            $routes = $routeCollection->all();

            $module = $this->getOption('module');

            if ($module) {
                $routes = array_filter(
                    $routes,
                    static function (Route $route) use ($module): bool {
                        $routeModule = $route->getModule();
                        return $routeModule !== null && strtolower($routeModule) === strtolower($module);
                    }
                );

                if ($routes === []) {
                    $this->error('The module is not found');
                    return;
                }
            }

            $rows = [];

            foreach ($routes as $route) {
                $rows[] = $this->composeTableRow($route, 50);
            }

            $table = new Table($this->resolveOutput());

            $table->setHeaderTitle('Routes')
                ->setHeaders(['MODULE', 'METHOD', 'URI', 'ACTION', 'MIDDLEWARE'])
                ->setRows($rows)
                ->render();
        } catch (ModuleException|RouteException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Composes a table row
     * @return array<int, string>
     */
    private function composeTableRow(Route $route, int $maxContentLength = 25): array
    {
        $controller = $route->getController();
        $actionName = $route->getAction();

        if ($controller !== null && $actionName !== null) {
            $action = $controller . '@' . $actionName;
        } else {
            $action = 'Closure';
        }

        if (mb_strlen($action) > $maxContentLength) {
            $action = mb_substr($action, 0, $maxContentLength) . '...';
        }

        $middlewares = $route->getMiddlewares();
        $middlewaresString = !empty($middlewares)
            ? implode(',', $middlewares)
            : '-';

        return [
            $route->getModule() ?? '',
            implode('|', $route->getMethods()),
            $route->getPattern(),
            $action,
            $middlewaresString,
        ];
    }
}
