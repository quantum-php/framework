<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\View\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\View
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const LAYOUT_NOT_SET = 'Layout is not set.';

    public const VIEW_NOT_RENDERED_YET = 'View not rendered yet.';
}
