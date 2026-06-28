<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Middlewares;

use {{MODULE_NAMESPACE}}\Services\CommentService;
use Quantum\Validation\Rule;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class CommentOwner
 * @package Modules\{{MODULE_NAME}}
 */
class CommentOwner extends BaseMiddleware
{
    public function apply(Request $request, Closure $next): Response
    {
        $uuid = (string)route_param('uuid');

        $request->set('uuid', $uuid);

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
        $this->registerCustomRules();

        $this->validator->setRules([
            'uuid' => [
                Rule::required(),
                Rule::commentOwner(),
            ],
        ]);
    }

    /**
     * Registers custom validation rules
     */
    private function registerCustomRules(): void
    {
        $this->validator->addRule('commentOwner', function ($commentUuid) {
            $comment = service(CommentService::class)->getComment($commentUuid);
            return !$comment->isEmpty() && $comment->user_uuid === auth()->user()->uuid;
        });
    }
}

