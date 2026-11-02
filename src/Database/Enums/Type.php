<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Enum Type
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
enum Type: string
{
    /**
     * Type integer
     */
    case INT = 'int';

    /**
     * Type tiny integer
     */
    case TINYINT = 'tinyint';

    /**
     * Type float
     */
    case FLOAT = 'float';

    /**
     * Type double
     */
    case DOUBLE = 'double';

    /**
     * Type decimal
     */
    case DECIMAL = 'decimal';

    /**
     * Type boolean
     */
    case BOOL = 'bool';

    /**
     * Type char
     */
    case CHAR = 'char';

    /**
     * Type varchar
     */
    case VARCHAR = 'varchar';

    /**
     * Type text
     */
    case TEXT = 'text';

    /**
     * Type binary
     */
    case BINARY = 'binary';

    /**
     * Type blob
     */
    case BLOB = 'blob';

    /**
     * Type enum
     */
    case ENUM = 'enum';

    /**
     * Type date
     */
    case DATE = 'date';

    /**
     * Type datetime
     */
    case DATETIME = 'datetime';

    /**
     * Type timestamp
     */
    case TIMESTAMP = 'timestamp';

    /**
     * Type json
     */
    case JSON = 'json';

    /**
     * Type geometry
     */
    case GEOMETRY = 'geometry';
}
