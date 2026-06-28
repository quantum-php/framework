<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\View\Exceptions;

use Quantum\View\Enums\ExceptionMessages;
use Quantum\App\Exceptions\BaseException;

/**
 * Class ViewException
 * @package Quantum\View
 */
class ViewException extends BaseException
{
    public static function noLayoutSet(): self
    {
        return new self(
            ExceptionMessages::LAYOUT_NOT_SET,
            E_ERROR
        );
    }

    public static function viewNotRendered(): self
    {
        return new self(
            ExceptionMessages::VIEW_NOT_RENDERED_YET,
            E_ERROR
        );
    }
}
