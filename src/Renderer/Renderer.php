<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Renderer;

use Quantum\Renderer\Contracts\TemplateRendererInterface;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\App\Exceptions\BaseException;

/**
 * Class Renderer
 * @package Quantum\Renderer
 * @method string render(string $view, array<string, mixed> $params = [])
 */
class Renderer
{
    private TemplateRendererInterface $adapter;

    public function __construct(TemplateRendererInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): TemplateRendererInterface
    {
        return $this->adapter;
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     * @throws BaseException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists($this->adapter, $method)) {
            throw RendererException::methodNotSupported($method, $this->adapter::class);
        }

        return $this->adapter->$method(...$arguments);
    }
}
