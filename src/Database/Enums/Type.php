<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Class Type
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
final class Type
{
    /**
     * Type integer
     */
    public const INT = 'int';

    /**
     * Type tiny integer
     */
    public const TINYINT = 'tinyint';

    /**
     * Type float
     */
    public const FLOAT = 'float';

    /**
     * Type double
     */
    public const DOUBLE = 'double';

    /**
     * Type decimal
     */
    public const DECIMAL = 'decimal';

    /**
     * Type boolean
     */
    public const BOOL = 'bool';

    /**
     * Type char
     */
    public const CHAR = 'char';

    /**
     * Type varchar
     */
    public const VARCHAR = 'varchar';

    /**
     * Type text
     */
    public const TEXT = 'text';

    /**
     * Type binary
     */
    public const BINARY = 'binary';

    /**
     * Type blob
     */
    public const BLOB = 'blob';

    /**
     * Type enum
     */
    public const ENUM = 'enum';

    /**
     * Type date
     */
    public const DATE = 'date';

    /**
     * Type datetime
     */
    public const DATETIME = 'datetime';

    /**
     * Type timestamp
     */
    public const TIMESTAMP = 'timestamp';

    /**
     * Type json
     */
    public const JSON = 'json';

    /**
     * Type geometry
     */
    public const GEOMETRY = 'geometry';

    private function __construct()
    {
    }
}
