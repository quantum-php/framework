<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

/**
 * Trait Length
 * @package Quantum\Validation
 */
trait Length
{
    /**
     * Checks the min Length
     */
    protected function minLen(string $value, int $minLength): bool
    {
        return mb_strlen($value) >= $minLength;
    }

    /**
     * Checks the max Length
     */
    protected function maxLen(string $value, int $maxLength): bool
    {
        return mb_strlen($value) <= $maxLength;
    }

    /**
     * Checks the exact length
     */
    protected function exactLen(string $value, int $exactLength): bool
    {
        return mb_strlen($value) === $exactLength;
    }
}
