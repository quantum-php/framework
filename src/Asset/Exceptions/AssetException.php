<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Asset\Exceptions;

use Quantum\Asset\Enums\ExceptionMessages;
use Exception;

/**
 * Class AssetException
 * @package Quantum\Asset
 */
class AssetException extends Exception
{
    public static function positionInUse(int $position, string $name): AssetException
    {
        return new self(
            _message(ExceptionMessages::POSITION_IN_USE, [(string) $position, $name]),
            E_WARNING
        );
    }

    public static function nameInUse(?string $name): AssetException
    {
        return new self(
            _message(ExceptionMessages::NAME_IN_USE, [(string) $name]),
            E_WARNING
        );
    }
}
