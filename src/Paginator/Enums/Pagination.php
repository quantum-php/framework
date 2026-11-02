<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Paginator\Enums;

/**
 * Enum Pagination
 * @package Quantum\Paginator
 * @codeCoverageIgnore
 */
enum Pagination: string
{
    /**
     * Pagination class name
     */
    case PAGINATION_CLASS = 'pagination';

    /**
     * Active class name
     */
    case PAGINATION_CLASS_ACTIVE = 'active';

    /**
     * Parameter name for per page
     */
    case PER_PAGE = 'per_page';

    /**
     * Parameter name for page number
     */
    case PAGE = 'page';
}
