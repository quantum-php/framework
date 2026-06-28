<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Di\Enums;

use Quantum\App\Enums\ExceptionMessages as BaseExceptionMessages;

/**
 * Class ExceptionMessages
 * @package Quantum\Di
 */
final class ExceptionMessages extends BaseExceptionMessages
{
    public const DEPENDENCY_NOT_REGISTERED = 'The dependency `{%1}` is not registered.';

    public const DEPENDENCY_ALREADY_REGISTERED = 'The dependency `{%1}` is already registered.';

    public const DEPENDENCY_NOT_INSTANTIABLE = 'The dependency `{%1}` is not instantiable.';

    public const INVALID_ABSTRACT_DEPENDENCY = 'The dependency `{%1}` is not valid abstract class.';

    public const CIRCULAR_DEPENDENCY = 'Circular dependency detected: `{%1}`';

    public const INVALID_CALLABLE = 'Invalid callable provided: expected Closure or array-style callable `{%1}`';
}
