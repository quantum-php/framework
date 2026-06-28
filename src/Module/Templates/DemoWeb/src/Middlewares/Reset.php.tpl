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
 * Class Reset
 * @package Modules\{{MODULE_NAME}}
 */
class Reset extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        $token = (string) route_param('token');

        $request->set('token', $token);

        if ($errorResponse = $this->validateRequest($request)) {
            return $errorResponse;
        }

        $request->set('reset_token', $token);

        return $next($request);
    }

    /**
     * @inheritDoc
     */
    protected function defineValidationRules(Request $request): void
    {
        if ($request->isMethod('post')) {
            $this->validator->setRules([
                'password' => [
                    Rule::required(),
                    Rule::minLen(6),
                ],
                'repeat_password' => [
                    Rule::required(),
                    Rule::minLen(6),
                    Rule::same('password'),
                ],
            ]);
        }

        $this->validator->setRules([
            'token' => [
                Rule::required(),
                Rule::exists(User::class, 'reset_token'),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        if ($request->isMethod('get') && isset($message['token'])) {
            return response()->html(partial('errors/404'), StatusCode::NOT_FOUND);
        }

        session()->setFlash('error', $message);
        return redirect(get_referrer() ?? base_url());
    }
}

