<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Enums;

/**
 * Class Pagination
 * @package Quantum\Paginator
 * @codeCoverageIgnore
 */
final class Pagination
{
    /**
     * Pagination class name
     */
    public const PAGINATION_CLASS = 'pagination';

    /**
     * Active class name
     */
    public const PAGINATION_CLASS_ACTIVE = 'active';

    /**
     * Parameter name for per page
     */
    public const PER_PAGE = 'per_page';

    /**
     * Parameter name for page number
     */
    public const PAGE = 'page';

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

    private function __construct()
    {
    }
}
