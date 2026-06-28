<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Module\Exceptions;

use Quantum\Module\Enums\ExceptionMessages;

/**
 * Class ModuleLoaderException
 * @package Quantum\Module
 */
class ModuleException extends \Exception
{
    public static function moduleRoutesNotFound(string $name): self
    {
        return new self(
            _message(ExceptionMessages::MODULE_ROUTES_NOT_FOUND, $name),
            E_ERROR
        );
    }

    public static function moduleConfigNotFound(): self
    {
        return new self(
            ExceptionMessages::MODULE_CONFIG_NOT_FOUND,
            E_ERROR
        );
    }

    public static function moduleCreationIncomplete(): self
    {
        return new self(
            ExceptionMessages::MODULE_CREATION_INCOMPLETE,
            E_ERROR
        );
    }

    public static function missingModuleTemplate(string $name): self
    {
        return new self(
            _message(ExceptionMessages::MISSING_MODULE_TEMPLATE, [$name]),
            E_ERROR
        );
    }

    public static function missingModuleDirectory(): self
    {
        return new self(
            ExceptionMessages::MISSING_MODULE_DIRECTORY,
            E_ERROR
        );
    }

    public static function moduleAlreadyExists(string $name): self
    {
        return new self(
            _message(ExceptionMessages::MODULE_ALREADY_EXISTS, [$name]),
            E_ERROR
        );
    }

    public static function directoryListingFailed(string $directory): self
    {
        return new self(
            _message(ExceptionMessages::DIRECTORY_LISTING_FAILED, [$directory]),
            E_ERROR
        );
    }
}
