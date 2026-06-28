<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Enums;

/**
 * Class PaginatorType
 * @package Quantum\Paginator
 * @codeCoverageIgnore
 */
final class PaginatorType
{
    public const ARRAY = 'array';

    public const MODEL = 'model';

    private function __construct()
    {
    }
}
