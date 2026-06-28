<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Jwt\Exceptions;

use Quantum\App\Exceptions\BaseException;
use Quantum\Jwt\Enums\ExceptionMessages;

/**
 * Class JwtException
 * @package Quantum\Jwt
 */
class JwtException extends BaseException
{
    public static function payloadNotFound(): self
    {
        return new self(
            ExceptionMessages::MISSING_PAYLOAD,
            E_WARNING
        );
    }
}
