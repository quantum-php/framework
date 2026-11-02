<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Enums;

/**
 * Enum PaginationLimit
 * @package Quantum\Paginator
 * @codeCoverageIgnore
 */
enum PaginationLimit
{
    /**
     * First page number
     */
    public const FIRST_PAGE_NUMBER = 1;

    /**
     * Minimum page items count
     */
    public const MINIMUM_PAGE_ITEMS_COUNT = 3;

    /**
     * Edge padding
     */
    public const EDGE_PADDING = 3;
}
