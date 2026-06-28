<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Validation\Rule;
use {{MODULE_NAMESPACE}}\Models\User;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Forget
 * @package Modules\{{MODULE_NAME}}
 */
class Forget extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post')) {
            if ($errorResponse = $this->validateRequest($request)) {
                return $errorResponse;
            }
        }

        return $next($request);
    }

    /**
     * @inheritDoc
     */
    protected function defineValidationRules(Request $request): void
    {
        $this->validator->setRules([
            'email' => [
                Rule::required(),
                Rule::email(),
                Rule::exists(User::class, 'email'),
            ],
        ]);
    }
}

