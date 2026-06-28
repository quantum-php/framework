<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Validation\Rule;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Update
 * @package Modules\{{MODULE_NAME}}
 */
class Update extends BaseMiddleware
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
            'firstname' => [
                Rule::required()
            ],
            'lastname' => [
                Rule::required()
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        session()->setFlash('error', $message);
        return redirectWith(base_url(true) . '/' . current_lang() . '/account-settings#account_profile', $request->all());
    }
}

