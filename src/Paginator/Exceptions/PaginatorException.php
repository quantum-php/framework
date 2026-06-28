<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Exceptions;

use Quantum\Paginator\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class PaginatorException
 * @package Quantum\Paginator
 */
class PaginatorException extends BaseException
{
    /**
     * @param string $missingParam
     */
    public static function missingRequiredParams(string $type, $missingParam): self
    {
        return new self(
            _message(ExceptionMessages::MISSING_REQUIRED_PARAMS, [$missingParam, ucfirst($type)]),
            E_WARNING
        );
    }
}
