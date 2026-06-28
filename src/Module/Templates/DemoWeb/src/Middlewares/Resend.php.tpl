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
 * Class Resend
 * @package Modules\{{MODULE_NAME}}
 */
class Resend extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        $code = (string) route_param('code');

        $request->set('code', $code);

        if ($errorResponse = $this->validateRequest($request)) {
            return $errorResponse;
        }

        return $next($request);
    }

    /**
     * @inheritDoc
     */
    protected function defineValidationRules(Request $request): void
    {
        $this->validator->setRules([
            'code' => [
                Rule::required(),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        session()->setFlash('error', $message);
        return redirect(base_url(true) . '/' . current_lang() . '/signin');
    }
}

