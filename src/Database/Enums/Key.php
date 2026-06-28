<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Class Key
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
final class Key
{
    /**
     * Primary key definition
     */
    public const PRIMARY = 'primary';

    /**
     * Index key definition
     */
    public const INDEX = 'index';

    /**
     * Unique key definition
     */
    public const UNIQUE = 'unique';

    /**
     * Full-text key definition
     */
    public const FULLTEXT = 'fulltext';

    /**
     * Spatial key definition
     */
    public const SPATIAL = 'spatial';

    private function __construct()
    {
    }
}
