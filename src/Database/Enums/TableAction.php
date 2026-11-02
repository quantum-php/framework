<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Enum TableAction
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
enum TableAction: int
{
    /**
     * Action create
     */
    case CREATE = 1;

    /**
     * Action alter
     */
    case ALTER = 2;

    /**
     * Action drop
     */
    case DROP = 3;

    /**
     * Action rename
     */
    case RENAME = 4;
}
