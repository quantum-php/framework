<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Router\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Router
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const ROUTE_METHODS_EMPTY = 'Route must define at least one HTTP method';

    public const CLOSURE_ROUTE_WITH_CONTROLLER = 'Closure route cannot define controller or action';

    public const CONTROLLER_ROUTE_INCOMPLETE = 'Controller route must define non-empty controller and action';

    public const MODULE_ROUTES_NOT_CLOSURE = 'Routes for module `{%1}` must return a Closure';

    public const NESTED_ROUTE_GROUPS_NOT_SUPPORTED = 'Nested route groups are not supported';

    public const MIDDLEWARES_OUTSIDE_ROUTE = 'middlewares() must be called inside a group or after a route definition';

    public const NAME_NOT_UNIQUE_IN_MODULE = 'Route name `{%1}` must be unique within module';

    public const CONTROLLER_WITHOUT_MODULE = 'Cannot resolve controller without module context';

    public const CACHEABLE_OUTSIDE_ROUTE = 'cacheable() must be called inside a group or after a route definition';

    public const RATE_LIMIT_OUTSIDE_ROUTE = 'rateLimit() must be called inside a group or after a route definition';

    public const CLOSURE_ROUTE_MISSING_HANDLER = 'Closure route is missing its closure handler';

    public const ACTION_NOT_FOUND_ON_CONTROLLER = 'Action `{%1}` not found on controller `{%2}`';

    public const INVALID_HANDLER_RESPONSE = 'Route handler `{%1}` must return `{%2}`';

    public const ROUTE_NOT_CLOSURE = 'Route is not a closure';

    public const NAME_BEFORE_ROUTE_DEFINITION = 'Names can not be set before route definition';

    public const ROUTE_PARAM_NAME_IN_USE = 'Route param name `{%1}` already in use';

    public const INVALID_ROUTE_PARAM_NAME = 'Route param names can only contain letters';
}
