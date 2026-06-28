<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Middleware\Middleware;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Auth
 * @package Modules\{{MODULE_NAME}}
 */
class Auth extends Middleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect(base_url(true) . '/' . current_lang() . '/signin');
        }

        return $next($request);
    }
}

