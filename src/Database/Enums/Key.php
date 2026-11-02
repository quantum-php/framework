<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Enum Key
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
enum Key: string
{
    /**
     * Primary key definition
     */
    case PRIMARY = 'primary';

    /**
     * Index key definition
     */
    case INDEX = 'index';

    /**
     * Unique key definition
     */
    case UNIQUE = 'unique';

    /**
     * Full-text key definition
     */
    case FULLTEXT = 'fulltext';

    /**
     * Spatial key definition
     */
    case SPATIAL = 'spatial';
}
