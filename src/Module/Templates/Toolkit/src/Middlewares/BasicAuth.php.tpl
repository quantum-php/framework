<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Middlewares;

use Quantum\Middleware\Middleware;
use Quantum\Loader\Setup;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class BasicAuth
 * @package Modules\Toolkit
 */
class BasicAuth extends Middleware
{
    public function apply(Request $request, Closure $next): Response
    {
        $userCredentials = $request->getBasicAuthCredentials();

        if (!$userCredentials || !$this->isValidCredentials($userCredentials)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    private function isValidCredentials(array $credentials): bool
    {
        if (!config()->has('basic_auth')) {
            config()->import(new Setup('config', 'basic_auth'));
        }

        $configCredentials = config()->get('basic_auth');

        return $credentials['username'] === $configCredentials['username']
            && $credentials['password'] === $configCredentials['password'];
    }

    private function unauthorizedResponse(): Response
    {
        return response()
            ->setHeader('WWW-Authenticate', 'Basic realm="Quantum Toolkit"')
            ->html(partial('errors' . DS . '401'), 401);
    }
}

