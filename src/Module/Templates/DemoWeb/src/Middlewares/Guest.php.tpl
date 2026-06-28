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
 * Class Guest
 * @package Modules\{{MODULE_NAME}}
 */
class Guest extends Middleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return redirect(get_referrer() ?? base_url(true) . '/' . current_lang());
        }

        return $next($request);
    }
}

