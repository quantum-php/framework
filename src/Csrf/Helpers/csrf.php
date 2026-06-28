<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\App\Exceptions\BaseException;
use Quantum\App\Exceptions\AppException;
use Quantum\Csrf\Csrf;
use Quantum\Di\Di;

/**
 * Gets the Csrf instance
 */
function csrf(): Csrf
{
    if (!Di::isRegistered(Csrf::class)) {
        Di::register(Csrf::class);
    }

    return Di::get(Csrf::class);
}

/**
 * Generates the CSRF token
 * @throws BaseException
 */
function csrf_token(): ?string
{
    $appKey = env('APP_KEY');

    if (!$appKey) {
        throw AppException::missingAppKey();
    }

    return csrf()->generateToken($appKey);
}
