<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Database\Enums;

/**
 * Enum Relation
 * @package Quantum\Database
 * @codeCoverageIgnore
 */
enum Relation: string
{
    case HAS_ONE = 'hasOne';
    case HAS_MANY = 'hasMany';
    case BELONGS_TO = 'belongsTo';
    case BELONGS_TO_MANY = 'belongsToMany';
}
