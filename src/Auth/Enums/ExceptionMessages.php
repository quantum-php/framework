<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Auth
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const INCORRECT_USER_SCHEMA = 'User schema does not contains all key fields.';

    public const VERIFICATION_CODE_EXPIRED = 'Verification code expired.';

    public const INCORRECT_VERIFICATION_CODE = 'Incorrect verification code.';

    public const INACTIVE_ACCOUNT = 'The account is not activated.';

    public const INCORRECT_CREDENTIALS = 'Incorrect credentials.';
}
