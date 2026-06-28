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
 * Class Editor
 * @package Modules\{{MODULE_NAME}}
 */
class Editor extends BaseMiddleware
{

    /**
     * Roles
     */
    const ROLES = ['admin', 'editor'];

    /**
     * Max image size in bytes (2MB)
     */
    private const MAX_IMAGE_SIZE_MB = 2 * 1024 * 1024;

    /**
     * Allowed image file extensions
     */
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpeg', 'jpg', 'png'];

    public function apply(Request $request, Closure $next): Response
    {
        if (!in_array(auth()->user()->role, self::ROLES)) {
            return redirect(base_url(true) . '/' . current_lang());
        }

        if ($request->isMethod('post') || $request->isMethod('put')) {
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
        if ($request->hasFile('image')) {
            $this->validator->setRules([
                'image' => [
                    Rule::fileSize(self::MAX_IMAGE_SIZE_MB),
                    Rule::fileExtension(...self::ALLOWED_IMAGE_EXTENSIONS),
                ],
            ]);
        }

        $this->validator->setRules([
            'title' => [
                Rule::required(),
                Rule::minLen(10),
                Rule::maxLen(50),
            ],
            'content' => [
                Rule::required(),
                Rule::minLen(10),
                Rule::maxLen(1000),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        $data = $request->all();

        unset($data['image']);

        session()->setFlash('error', $this->validator->getErrors());
        return redirectWith(get_referrer() ?? base_url(), $data);
    }
}

