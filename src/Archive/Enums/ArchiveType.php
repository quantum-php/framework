<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Archive\Enums;

/**
 * Class ArchiveType
 * @package Quantum\Archive
 * @codeCoverageIgnore
 */
final class ArchiveType
{
    public const PHAR = 'phar';

    public const ZIP = 'zip';

    private function __construct()
    {
    }
}
