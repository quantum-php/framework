<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

/**
 * Trait Lists
 * @package Quantum\Validation
 */
trait Lists
{
    /**
     * Validates that the field value is contained within a given string.
     */
    protected function contains(string $value, string $haystack): bool
    {
        $value = trim(strtolower($value));
        $haystack = trim(strtolower($haystack));

        return str_contains($haystack, $value);
    }

    /**
     * Verifies that a value is contained within the pre-defined value set.
     */
    protected function containsList(string $value, string ...$list): bool
    {
        $value = trim(strtolower($value));

        $list = array_map(fn (string $item): string => trim(strtolower($item)), $list);

        return in_array($value, $list);
    }

    /**
     * Verifies that a value is not contained within the pre-defined value set.
     */
    protected function doesntContainsList(string $value, string ...$list): bool
    {
        $value = trim(strtolower($value));

        $list = array_map(fn (string $item): string => trim(strtolower($item)), $list);

        return !in_array($value, $list);
    }
}
