<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

/**
 * Trait Type
 * @package Quantum\Validation
 */
trait Type
{
    /**
     * Checks the alpha characters
     * @param mixed $value
     */
    protected function alpha($value): bool
    {
        return preg_match('/^[\p{L}]+$/u', (string) $value) === 1;
    }

    /**
     * Checks the alpha and numeric characters
     * @param mixed $value
     */
    protected function alphaNumeric($value): bool
    {
        return preg_match('/^[\p{L}0-9]+$/u', (string) $value) === 1;
    }

    /**
     * Checks the alpha and dash characters
     * @param mixed $value
     */
    protected function alphaDash($value): bool
    {
        return preg_match('/^[\p{L}_-]+$/u', (string) $value) === 1;
    }

    /**
     * Checks the alphanumeric and space characters
     * @param mixed $value
     */
    protected function alphaSpace($value): bool
    {
        return preg_match('/^[\p{L}0-9\s]+$/u', (string) $value) === 1;
    }

    /**
     * Checks the numeric value
     * @param mixed $value
     */
    protected function numeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Checks the integer value
     * @param mixed $value
     */
    protected function integer($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Checks the float value
     * @param mixed $value
     */
    protected function float($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * Checks the boolean value
     * @param mixed $value
     */
    protected function boolean($value): bool
    {
        $booleans = ['1', 'true', true, 1, '0', 'false', false, 0, 'yes', 'no', 'on', 'off'];

        return in_array($value, $booleans, true);
    }

    /**
     * Determines if the provided numeric value is lower to a specific value
     * @param mixed $value
     * @param mixed $minValue
     */
    protected function minNumeric($value, $minValue): bool
    {
        return is_numeric($value) && is_numeric($minValue) && ($value >= $minValue);
    }

    /**
     * Determines if the provided numeric value is higher to a specific value
     * @param mixed $value
     * @param mixed $maxValue
     */
    protected function maxNumeric($value, $maxValue): bool
    {
        return is_numeric($value) && is_numeric($maxValue) && ($value <= $maxValue);
    }
}
