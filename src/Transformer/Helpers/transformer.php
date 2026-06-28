<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Transformer\Contracts\TransformerInterface;
use Quantum\Transformer\Transformer;

/**
 * Transforms the data by given transformer signature
 * @param array<mixed> $data
 * @return array<mixed>
 */
function transform(array $data, TransformerInterface $transformer): array
{
    return Transformer::transform($data, $transformer);
}
