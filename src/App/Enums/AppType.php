<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Enums;

/**
 * Class AppType
 * @package Quantum\App
 * @codeCoverageIgnore
 */
final class AppType
{
    public const WEB = 'web';

    public const CONSOLE = 'console';

    private function __construct()
    {
    }
}
