<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Http\Enums\StatusCode;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Auth
 * @package Modules\{{MODULE_NAME}}
 */
class Auth extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $this->respondWithError($request,
                t('validation.unauthorizedRequest'),
                StatusCode::UNAUTHORIZED
            );
        }

        return $next($request);
    }
}

