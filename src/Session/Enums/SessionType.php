<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Session\Enums;

/**
 * Class SessionType
 * @package Quantum\Session
 * @codeCoverageIgnore
 */
final class SessionType
{
    public const NATIVE = 'native';

    public const DATABASE = 'database';

    private function __construct()
    {
    }
}
