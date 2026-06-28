<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router\Exceptions;

use Quantum\Router\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class RouteException
 * @package Quantum\Router
 */
class RouteException extends BaseException
{
    public static function noHttpMethods(): self
    {
        return new self(
            ExceptionMessages::ROUTE_METHODS_EMPTY,
            E_ERROR
        );
    }

    public static function closureWithController(): self
    {
        return new self(
            ExceptionMessages::CLOSURE_ROUTE_WITH_CONTROLLER,
            E_WARNING
        );
    }

    public static function incompleteControllerRoute(): self
    {
        return new self(
            ExceptionMessages::CONTROLLER_ROUTE_INCOMPLETE,
            E_ERROR
        );
    }

    public static function moduleRoutesNotClosure(string $module): self
    {
        return new self(
            _message(ExceptionMessages::MODULE_ROUTES_NOT_CLOSURE, [$module]),
            E_ERROR
        );
    }

    public static function nestedGroups(): self
    {
        return new self(
            ExceptionMessages::NESTED_ROUTE_GROUPS_NOT_SUPPORTED,
            E_WARNING
        );
    }

    public static function middlewaresOutsideRoute(): self
    {
        return new self(
            ExceptionMessages::MIDDLEWARES_OUTSIDE_ROUTE,
            E_WARNING
        );
    }

    public static function nonUniqueNameInModule(string $name): self
    {
        return new self(
            _message(ExceptionMessages::NAME_NOT_UNIQUE_IN_MODULE, [$name]),
            E_WARNING
        );
    }

    public static function controllerWithoutModule(): self
    {
        return new self(
            ExceptionMessages::CONTROLLER_WITHOUT_MODULE,
            E_ERROR
        );
    }

    public static function cacheableOutsideRoute(): self
    {
        return new self(
            ExceptionMessages::CACHEABLE_OUTSIDE_ROUTE,
            E_WARNING
        );
    }

    public static function rateLimitOutsideRoute(): self
    {
        return new self(
            ExceptionMessages::RATE_LIMIT_OUTSIDE_ROUTE,
            E_WARNING
        );
    }

    public static function closureHandlerMissing(): self
    {
        return new self(
            ExceptionMessages::CLOSURE_ROUTE_MISSING_HANDLER,
            E_ERROR
        );
    }

    public static function actionNotFound(string $action, string $controller): self
    {
        return new self(
            _message(
                ExceptionMessages::ACTION_NOT_FOUND_ON_CONTROLLER,
                [$action, $controller]
            ),
            E_ERROR
        );
    }

    public static function invalidHandlerResponse(string $handler, string $expectedType): self
    {
        return new self(
            _message(
                ExceptionMessages::INVALID_HANDLER_RESPONSE,
                [$handler, $expectedType]
            ),
            E_ERROR
        );
    }

    public static function notClosure(): self
    {
        return new self(
            ExceptionMessages::ROUTE_NOT_CLOSURE,
            E_WARNING
        );
    }

    public static function nameBeforeDefinition(): self
    {
        return new self(
            ExceptionMessages::NAME_BEFORE_ROUTE_DEFINITION,
            E_WARNING
        );
    }

    public static function paramNameNotAvailable(string $name): self
    {
        return new self(
            _message(ExceptionMessages::ROUTE_PARAM_NAME_IN_USE, [$name]),
            E_WARNING
        );
    }

    public static function paramNameNotValid(): self
    {
        return new self(
            ExceptionMessages::INVALID_ROUTE_PARAM_NAME,
            E_WARNING
        );
    }
}
