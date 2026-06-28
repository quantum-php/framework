<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer\Contracts;

/**
 * Interface TemplateRendererInterface
 * @package Quantum\Renderer
 */
interface TemplateRendererInterface
{
    /**
     * Renders the template
     * @param array<string, mixed> $params
     */
    public function render(string $view, array $params = []): string;
}
