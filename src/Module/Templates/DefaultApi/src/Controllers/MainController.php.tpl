<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\Http\Response;

/**
 * Class MainController
 * @package Modules\Api
 */
class MainController
{
    /**
     * Status error
     */
    const STATUS_ERROR = 'error';

    /**
     * Status success
     */
    const STATUS_SUCCESS = 'success';

    /**
     * CSRF verification
     */
    public bool $csrfVerification = false;

    /**
     * Action - success response
     */
    public function index(): Response
    {
        return response()->json([
            'status' => 'success',
            'message' => '{{MODULE_NAME}} module.'
        ]);
    }
}

