<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Exceptions;

use Quantum\HttpClient\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class HasherException
 * @package Quantum\HttpClient
 */
class HttpClientException extends BaseException
{
    public static function requestNotCreated(): self
    {
        return new self(
            ExceptionMessages::REQUEST_NOT_CREATED,
            E_WARNING
        );
    }
}
