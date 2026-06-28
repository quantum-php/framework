<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Signout
 * @package Modules\{{MODULE_NAME}}
 */
class Signout extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if (!$request->hasHeader('refresh_token')) {
            return $this->respondWithError($request,
                [t('validation.nonExistingRecord', 'token')]
            );
        }

        return $next($request);
    }
}

