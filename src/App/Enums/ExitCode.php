<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Enums;

/**
 * Class ExitCode
 * @package Quantum\App
 * @codeCoverageIgnore
 */
final class ExitCode
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const INVALID = 2;

    private function __construct()
    {
    }
}
