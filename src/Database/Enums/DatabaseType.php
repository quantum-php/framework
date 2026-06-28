<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Class DatabaseType
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
final class DatabaseType
{
    public const SLEEKDB = 'sleekdb';

    public const MYSQL = 'mysql';

    public const SQLITE = 'sqlite';

    public const PGSQL = 'pgsql';

    private function __construct()
    {
    }
}
