<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Exceptions;

use Quantum\Lang\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class LangException
 * @package Quantum\Lang
 */
class LangException extends BaseException
{
    public static function translationsNotFound(): self
    {
        return new self(
            ExceptionMessages::TRANSLATION_FILES_NOT_FOUND,
            E_WARNING
        );
    }

    public static function misconfiguredDefaultConfig(): self
    {
        return new self(
            ExceptionMessages::MISCONFIGURED_DEFAULT_LANG,
            E_WARNING
        );
    }

    public static function invalidProviderResponse(string $provider): self
    {
        return new self(
            _message(ExceptionMessages::INVALID_PROVIDER_RESPONSE, [$provider]),
            E_WARNING
        );
    }

    public static function providerRequestFailed(string $provider, ?string $details = null): self
    {
        $suffix = $details ? ': ' . $details : '';

        return new self(
            _message(ExceptionMessages::PROVIDER_REQUEST_FAILED, [$provider, $suffix]),
            E_WARNING
        );
    }
}
