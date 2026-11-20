<?php

namespace Quantum\Tests\Unit;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\App\Factories\AppFactory;
use Quantum\Router\MatchedRoute;
use Quantum\Tracer\ErrorHandler;
use Quantum\Storage\FileSystem;
use PHPUnit\Framework\TestCase;
use Quantum\Debugger\Debugger;
use Quantum\App\Enums\AppType;
use Quantum\Di\DiContainer;
use Quantum\App\AppContext;
use Quantum\Config\Config;
use Quantum\Router\Route;
use Quantum\Http\Response;
use ReflectionFunction;
use ReflectionClass;
use ReflectionMethod;
use Quantum\App\App;
use Quantum\Di\Di;
use ReflectionProperty;
use Closure;

abstract class AppTestCase extends TestCase
{
    protected AppContext $context;

    /** @var FileSystem */
    protected $fs;

    public function setUp(): void
    {
        AppFactory::create(AppType::WEB, $this->appBaseDir());

        environment()->setMutable(true);

        $this->fs = FileSystemFactory::get();
    }

    public function tearDown(): void
    {
        request()->setMatchedRoute(null);
        request()->flush();

        AppFactory::destroy(AppType::WEB);

        if (Di::isRegistered(Response::class)) {
            response()->flush();
        }

        if (Di::isRegistered(Config::class)) {
            config()->flush();
        }
        if (Di::isRegistered(Debugger::class)) {
            Di::get(Debugger::class)->resetStore();
        }

        $this->clearAppContext();

        $this->removeFrameworkErrorHandlers();
    }

    /**
     * Removes the error and exception handlers that Quantum\Tracer\ErrorHandler registered,
     * one pair per boot, and stops at the first handler that is not Quantum's
     */
    public static function removeFrameworkErrorHandlers(): void
    {
        while (self::isFrameworkHandler(set_error_handler(static fn (): bool => false))) {
            restore_error_handler();
            restore_error_handler();
        }

        restore_error_handler();

        while (self::isFrameworkHandler(set_exception_handler(static function (): void {
        }))) {
            restore_exception_handler();
            restore_exception_handler();
        }

        restore_exception_handler();
    }

    private static function isFrameworkHandler(mixed $handler): bool
    {
        return $handler instanceof Closure && (new ReflectionFunction($handler))->getClosureThis() instanceof ErrorHandler;
    }

    /**
     * The folder of the test app the tests boot on
     */
    protected function appBaseDir(): string
    {
        return PROJECT_ROOT;
    }

    protected function createContext(string $type = AppType::WEB): AppContext
    {
        $context = new AppContext($this->appBaseDir(), new DiContainer(), $type);
        App::setContext($context);

        return $context;
    }

    /**
     * Replaces the app context with one of the given type that keeps the current container
     */
    protected function useAppType(string $type): void
    {
        App::setContext(new AppContext(PROJECT_ROOT, App::getContext()->getContainer(), $type));
    }

    protected function clearAppContext(): void
    {
        $prop = new ReflectionProperty(App::class, 'context');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    protected function setPrivateProperty($object, $property, $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        if (is_string($object)) {
            $property->setValue(null, $value);
        } else {
            $property->setValue($object, $value);
        }
    }

    protected function getPrivateProperty($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    protected function invokePrivateMethod(object $object, string $method, array $args = [])
    {
        $reflectionMethod = new ReflectionMethod($object, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $args);
    }

    protected function createFile(string $filePath, string $content)
    {
        $this->fs->put($filePath, $content);
    }

    protected function removeFile(string $filePath)
    {
        if ($this->fs->exists($filePath)) {
            $this->fs->remove($filePath);
        }
    }

    protected function testRequest(
        string $uri,
        string $method = 'GET',
        array $body = [],
        array $headers = []
    ) {
        request()->create($method, $uri, $body, $headers);

        $route = new Route(
            [$method],
            $uri,
            'TestController',
            'testAction'
        );
        $route->module('Test');

        $matchedRoute = new MatchedRoute($route, []);
        request()->setMatchedRoute($matchedRoute);
    }
}
