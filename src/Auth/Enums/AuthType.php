<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Auth\Enums;

/**
 * Class AuthType
 * @package Quantum\Auth
 * @codeCoverageIgnore
 */
final class AuthType
{
    public const SESSION = 'session';

    public const JWT = 'jwt';

    private function __construct()
    {
    }
}
