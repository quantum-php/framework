<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer\Exceptions;

use Quantum\App\Exceptions\BaseException;

/**
 * Class RendererException
 * @package Quantum\Renderer
 * @codeCoverageIgnore
 */
class RendererException extends BaseException
{
    public static function twigNotInstalled(): self
    {
        return new self(
            'The Twig renderer requires the optional `twig/twig` package. ' .
            'Install a security-supported release separately; Twig ^3.27 requires PHP 8.1 or later: ' .
            '`composer require twig/twig:^3.27`.'
        );
    }
}
