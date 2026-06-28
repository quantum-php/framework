<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Http\Enums\StatusCode;
use Quantum\Middleware\Middleware;
use Quantum\Validation\Validator;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class BaseMiddleware
 * @package Modules\{{MODULE_NAME}}
 */
abstract class BaseMiddleware extends Middleware
{
    protected $validator;

    /**
     * BaseMiddleware constructor.
     */
    public function __construct(Request $request)
    {
        $this->validator = new Validator();

        $this->defineValidationRules($request);
    }

    /**
     * Define validation rules specific to middleware.
     */
    protected function defineValidationRules(Request $request): void
    {
        // default no-op: subclasses override if needed
    }

    /**
     * Validate the request and respond with error if invalid.
     */
    protected function validateRequest(Request $request): ?Response
    {
        if (!$this->validator->isValid($request->all())) {
            return $this->respondWithError($request, $this->validator->getErrors());
        }

        return null;
    }

    /**
     * Handles error response logic.
     */
    protected function respondWithError(Request $request, $message, int $status = StatusCode::UNPROCESSABLE_ENTITY): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }
}

