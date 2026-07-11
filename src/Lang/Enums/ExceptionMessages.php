<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Lang\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Lang
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const TRANSLATION_FILES_NOT_FOUND = 'Translation files not found.';

    public const MISCONFIGURED_DEFAULT_LANG = 'Misconfigured lang default config.';

    public const MISCONFIGURED_DEFAULT_ADAPTER = 'Misconfigured lang default adapter config.';

    public const PAYLOAD_ENCODING_FAILED = 'The translation payload could not be encoded for `{%1}`.';

    public const INVALID_PROVIDER_RESPONSE = 'The provider `{%1}` returned an invalid translation response.';

    public const PROVIDER_REQUEST_FAILED = 'The translation request to `{%1}` failed{%2}.';
}
