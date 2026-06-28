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
 * Class Comment
 * @package Modules\{{MODULE_NAME}}
 */
class Comment extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
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
            'content' => [
                Rule::required(),
                Rule::minLen(2),
                Rule::maxLen(100),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request,
        $message
    ): Response
    {
        $data = $request->all();

        session()->setFlash('error', $this->validator->getErrors());
        return redirectWith(get_referrer() ?? base_url(), $data);
    }
}

