<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router;

use Quantum\Router\Exceptions\RouteException;
use Quantum\Csrf\Exceptions\CsrfException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Response;
use Quantum\Http\Request;
use ReflectionException;
use Quantum\Csrf\Csrf;
use Quantum\Di\Di;
use Closure;

/**
 * Class RouteDispatcher
 * @package Quantum\Router
 */
final class RouteDispatcher
{
    /**
     * Dispatch a matched route.
     * @throws ReflectionException|CsrfException|DiException|RouteException
     */
    public function dispatch(MatchedRoute $matched, Request $request): Response
    {
        $route = $matched->getRoute();
        $params = $matched->getParams();

        if ($route->isClosure()) {
            $closure = $route->getClosure();

            if ($closure === null) {
                throw RouteException::closureHandlerMissing();
            }

            return $this->requireResponse(
                $this->invoke($closure, $params),
                'closure route'
            );
        }

        $callable = $this->resolveControllerCallable($route);
        [$controller] = $callable;

        $this->verifyCsrf($controller, $request);

        $this->callHook($controller, '__before', $params);

        $response = $this->requireResponse(
            $this->invoke($callable, $params),
            $route->getController() . '::' . $route->getAction()
        );

        $this->callHook($controller, '__after', $params);

        return $response;
    }

    /**
     * Resolve controller callable from the route definition.
     * @return array{0: object, 1: string}
     * @throws RouteException
     */
    private function resolveControllerCallable(Route $route): array
    {
        $controllerClass = $route->getController();
        $action = $route->getAction();

        if ($controllerClass === null || $action === null) {
            throw RouteException::incompleteControllerRoute();
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw RouteException::actionNotFound($controllerClass, $action);
        }

        return [$controller, $action];
    }

    /**
     * Invoke a callable with parameters resolved via DI autowiring.
     * @param Closure|array{0: object, 1: string} $callable
     * @param array<string, mixed> $params
     * @return mixed
     * @throws DiException
     */
    private function invoke(Closure|array $callable, array $params)
    {
        if (!is_callable($callable)) {
            throw DiException::invalidCallable();
        }

        $closure = Closure::fromCallable($callable);

        return $closure(...Di::autowire($closure, $params));
    }

    /**
     * Enforce a Response return contract for route handlers.
     * @param mixed $result
     * @throws RouteException
     */
    private function requireResponse($result, string $handler): Response
    {
        if (!$result instanceof Response) {
            throw RouteException::invalidHandlerResponse($handler, Response::class);
        }

        return $result;
    }

    /**
     * Invoke a controller lifecycle hook if it exists.
     * @param array<string, mixed> $params
     * @throws DiException|ReflectionException
     */
    private function callHook(object $controller, string $hook, array $params): void
    {
        if (method_exists($controller, $hook)) {
            $this->invoke([$controller, $hook], $params);
        }
    }

    /**
     * Verify CSRF token if controller requires it and request method applies.
     * @throws CsrfException
     */
    private function verifyCsrf(?object $controller, Request $request): void
    {
        if (
            $controller !== null &&
            property_exists($controller, 'csrfVerification') &&
            $controller->csrfVerification === true &&
            in_array($request->getMethod(), Csrf::METHODS, true)
        ) {
            csrf()->checkToken($request);
        }
    }
}
