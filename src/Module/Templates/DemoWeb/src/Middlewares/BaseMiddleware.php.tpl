<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use Quantum\Validation\Validator;
use Quantum\Middleware\Middleware;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class BaseMiddleware
 * @package Modules\{{MODULE_NAME}}
 */
abstract class BaseMiddleware extends Middleware
{
    protected $validator;

    /**
     * Initialize Validator and define rules.
     */
    public function __construct(Request $request)
    {
        $this->validator = new Validator();

        $this->defineValidationRules($request);
    }

    protected function validateRequest(Request $request): ?Response
    {
        if (!$this->validator->isValid($request->all())) {
            return $this->respondWithError($request, $this->validator->getErrors());
        }

        return null;
    }

    /**
     * Define validation rules specific to middleware.
     */
    protected function defineValidationRules(Request $request): void
    {
        // default no-op: subclasses override if needed
    }

    /**
     * Handles error response logic.
     */
    protected function respondWithError(Request $request, $message): Response
    {
        // default no-op: subclasses override if needed
        return response();
    }
}

