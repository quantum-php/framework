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
 * Class Signup
 * @package Modules\{{MODULE_NAME}}
 */
class Signup extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post')) {
            $captchaName = captcha()->getName();

            if ($request->has($captchaName . '-response')) {
                $request->set('captcha', $request->get($captchaName . '-response'));
                $request->delete($captchaName . '-response');
            }

            if ($errorResponse = $this->validateRequest($request)) {
                return $errorResponse;
            }

            $request->delete('captcha');
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
                Rule::unique(User::class, 'email'),
            ],
            'password' => [
                Rule::required(),
                Rule::minLen(6),
            ],
            'firstname' => [
                Rule::required(),
            ],
            'lastname' => [
                Rule::required(),
            ],
//            'captcha' => [
//                Rule::required(),
//                Rule::captcha(),
//            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        session()->setFlash('error', $message);
        return redirectWith(base_url(true) . '/' . current_lang() . '/signup', $request->all());
    }
}

