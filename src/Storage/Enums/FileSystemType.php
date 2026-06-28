<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Enums;

/**
 * Class FileSystemType
 * @package Quantum\Storage
 * @codeCoverageIgnore
 */
final class FileSystemType
{
    public const LOCAL = 'local';

    public const DROPBOX = 'dropbox';

    public const GDRIVE = 'gdrive';

    private function __construct()
    {
    }
}
