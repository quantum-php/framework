<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Middlewares;

use Modules\Toolkit\Services\DatabaseService;
use Quantum\Service\Factories\ServiceFactory;
use Quantum\Validation\Rule;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Closure;

/**
 * Class CreateTable
 * @package Modules\Toolkit
 */
class CreateTable extends BaseMiddleware
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
        $this->registerCustomRules();

        $originalTableName = $request->get('originalName');

        $this->validator->setRules([
            'table' => [
                Rule::required(),
                Rule::uniqueTableName($originalTableName)
            ],
            'data' => [
                Rule::required(),
                Rule::jsonString(),
                Rule::nonEmptyJson()
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function respondWithError(Request $request, $message): Response
    {
        session()->setFlash('error', $message);
        return redirect(get_referrer() ?? base_url());
    }

    /**
     * Registers custom validation rules
     */
    private function registerCustomRules(): void
    {
        $this->validator->addRule('uniqueTableName', function ($value, $except = null) {
            $databaseService = ServiceFactory::get(DatabaseService::class);
            return !$databaseService->tableExists($value, $except);
        });

        $this->validator->addRule('nonEmptyJson', function ($value) {
            $decoded = json_decode($value, true);
            return is_array($decoded) && !empty($decoded);
        });
    }
}

