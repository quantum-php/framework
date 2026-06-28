<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Http\Enums\StatusCode;
use Quantum\Validation\Rule;
use {{MODULE_NAMESPACE}}\Models\User;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Verify
 * @package Modules\{{MODULE_NAME}}
 */
class Verify extends BaseMiddleware
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
            'otp' => [
                Rule::required()
            ],
            'code' => [
                Rule::required(),
                Rule::exists(User::class, 'otp_token'),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        if ($request->isMethod('get') && isset($message['code'])) {
            return response()->html(partial('errors/404'), StatusCode::NOT_FOUND);
        }

        session()->setFlash('error', $message);
        return redirectWith(base_url(true) . '/' . current_lang() . '/verify', $request->all());
    }
}

