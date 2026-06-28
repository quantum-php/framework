<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\View;

/**
 * Class RawParam
 * @package Quantum\View
 */
class RawParam
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Gets the raw value.
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
