<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer\Enums;

/**
 * Class RendererType
 * @package Quantum\Renderer
 * @codeCoverageIgnore
 */
final class RendererType
{
    public const HTML = 'html';

    public const TWIG = 'twig';

    private function __construct()
    {
    }
}
