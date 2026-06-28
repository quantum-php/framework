<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Validation\Rule;
use Quantum\Hasher\Hasher;
use {{MODULE_NAMESPACE}}\Models\User;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class Password
 * @package Modules\{{MODULE_NAME}}
 */
class Password extends BaseMiddleware
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
        $this->registerCustomRules($request);

        $this->validator->setRules([
            'current_password' => [
                Rule::required(),
                Rule::passwordCheck(),
            ],
            'new_password' => [
                Rule::required(),
                Rule::minLen( 6),
            ],
            'confirm_password' => [
                Rule::required(),
                Rule::same('new_password'),
            ],
        ]);
    }

    /**
     * Registers custom validation rules
     */
    private function registerCustomRules(Request $request)
    {
        $this->validator->addRule('passwordCheck', function () use ($request) {
            $user = model(User::class)->findOneBy('uuid', auth()->user()->uuid);
            return $user && (new Hasher())->check($request->get('current_password'), $user->password);
        });
    }
}

