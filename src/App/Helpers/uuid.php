<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Ramsey\Uuid\Uuid;

/**
 * Generate a standard v4 UUID (random)
 */
function uuid_random(): string
{
    return Uuid::uuid4()->toString();
}

/**
 * Generate an ordered UUID (time-based)
 */
function uuid_ordered(): string
{
    return Uuid::uuid1()->toString();
}
