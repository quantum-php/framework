<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Transformer;

use Quantum\Transformer\Contracts\TransformerInterface;

/**
 * Class TransformerManager
 * @package Quantum\Transformer
 */
class Transformer
{
    /**
     * Applies the transformer on each item of the array
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function transform(array $data, TransformerInterface $transformer): array
    {
        return array_map([$transformer, 'transform'], $data);
    }
}
