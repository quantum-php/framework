<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Transformer\Contracts;

/**
 * Interface TransformerInterface
 * @package Quantum\Transformer
 */
interface TransformerInterface
{
    /**
     * Defines the transformer signature
     * @param mixed $item
     * @return mixed
     */
    public function transform($item);
}
