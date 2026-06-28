<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Encryption
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const PUBLIC_KEY_MISSING = 'Public key is not provided';

    public const PRIVATE_KEY_MISSING = 'Private key is not provided';

    public const INVALID_CIPHER = 'The cipher is invalid';

}
