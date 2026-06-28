<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App;

use Quantum\Environment\Environment;
use Quantum\Router\RouteCollection;
use Quantum\Di\DiContainer;
use Quantum\Config\Config;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class AppContext
 * @package Quantum\App
 */
class AppContext
{
    private string $baseDir;

    private DiContainer $container;

    public function __construct(string $baseDir, DiContainer $container)
    {
        $this->baseDir = $baseDir;
        $this->container = $container;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    public function getContainer(): DiContainer
    {
        return $this->container;
    }

    public function getEnvironment(): Environment
    {
        return $this->resolveFromContainer(Environment::class);
    }

    public function getConfig(): Config
    {
        return $this->resolveFromContainer(Config::class);
    }

    public function getRequest(): Request
    {
        return $this->resolveFromContainer(Request::class);
    }

    public function getResponse(): Response
    {
        return $this->resolveFromContainer(Response::class);
    }

    public function getRoutes(): RouteCollection
    {
        return $this->resolveFromContainer(RouteCollection::class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function resolveFromContainer(string $class)
    {
        return $this->container->get($class);
    }
}
