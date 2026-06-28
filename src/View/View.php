<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\View;

use Quantum\Database\Exceptions\DatabaseException;
use Quantum\Session\Exceptions\SessionException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Asset\Exceptions\AssetException;
use Quantum\View\Exceptions\ViewException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\ResourceCache\ViewCache;
use Quantum\Asset\AssetManager;
use Quantum\Debugger\Debugger;
use Quantum\Renderer\Renderer;
use ReflectionException;
use Psr\Log\LogLevel;

/**
 * Class View
 * @package Quantum\View
 */
class View
{
    private Renderer $renderer;

    private AssetManager $assetManager;

    private Debugger $debugger;

    private ViewCache $viewCache;

    /**
     * Layout file
     */
    private ?string $layoutFile = null;

    /**
     * Rendered view
     */
    private ?string $viewContent = null;

    /**
     * Assets to be included
     * @var array<string, mixed>
     */
    private array $assets = [];

    /**
     * View params
     * @var array<string, mixed>
     */
    private array $params = [];

    public function __construct(
        Renderer $renderer,
        AssetManager $assetManager,
        Debugger $debugger,
        ViewCache $viewCache
    ) {
        $this->renderer = $renderer;
        $this->assetManager = $assetManager;
        $this->debugger = $debugger;
        $this->viewCache = $viewCache;
    }

    /**
     * Sets a layout
     * @param array<string, mixed> $assets
     */
    public function setLayout(?string $layoutFile, array $assets = []): void
    {
        $this->layoutFile = $layoutFile;

        if ($assets !== []) {
            $this->assets = array_merge($this->assets, $assets);
        }
    }

    /**
     * Gets the layout
     */
    public function getLayout(): ?string
    {
        return $this->layoutFile;
    }

    /**
     * Sets view parameter
     * @param mixed $value
     */
    public function setParam(string $key, $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * @param mixed $value
     */
    public function setRawParam(string $key, $value): void
    {
        $this->params[$key] = new RawParam($value);
    }

    /**
     * Gets the view parameter
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        $param = $this->params[$key] ?? null;

        if ($param instanceof RawParam) {
            return $param->getValue();
        }

        return $param;
    }

    /**
     * Sets multiple view parameters
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
    }

    /**
     * Gets all view parameters
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return array_map(fn ($param) => ($param instanceof RawParam) ? $param->getValue() : $param, $this->params);
    }

    /**
     * Flushes the view params
     */
    public function flushParams(): void
    {
        $this->params = [];
    }

    /**
     * Renders the view.
     * @param array<string, mixed> $params
     * @throws ViewException|AssetException|SessionException|DatabaseException|ConfigException|BaseException|DiException|ReflectionException
     */
    public function render(string $viewFile, array $params = []): ?string
    {
        $layoutFile = $this->layoutFile;

        if (!$layoutFile) {
            throw ViewException::noLayoutSet();
        }

        if ($params !== []) {
            $this->params = array_merge($this->params, $params);
        }

        $this->viewContent = $this->renderFile($viewFile);

        if ($this->assets !== []) {
            $this->assetManager->register($this->assets);
        }

        if ($this->debugger->isEnabled()) {
            $this->updateDebugger($viewFile);
        }

        $layoutContent = $this->renderFile($layoutFile);

        if ($this->viewCache->isEnabled()) {
            $uri = request()->getUri();
            if ($uri !== null) {
                $layoutContent = $this->viewCache
                    ->set($uri, $layoutContent)
                    ->get($uri);
            }
        }

        return $layoutContent;
    }

    /**
     * Renders partial view.
     * @param array<string, mixed> $params
     */
    public function renderPartial(string $viewFile, array $params = []): string
    {
        if ($params !== []) {
            $this->params = array_merge($this->params, $params);
        }

        return $this->renderFile($viewFile);
    }

    /**
     * Gets the rendered view content.
     * @throws ViewException
     */
    public function getContent(): ?string
    {
        if ($this->viewContent === null) {
            throw ViewException::viewNotRendered();
        }

        return $this->viewContent;
    }

    /**
     * Renders the view
     */
    private function renderFile(string $viewFile): string
    {
        $filteredParams = $this->xssFilter($this->params);

        return $this->renderer->render($viewFile, $filteredParams);
    }

    /**
     * XSS Filter
     * @param mixed $params
     * @return mixed
     */
    private function xssFilter($params)
    {
        if ($params instanceof RawParam) {
            return $params->getValue();
        }

        if (is_string($params)) {
            return $this->sanitizeHtml($params);
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $params[$key] = $this->xssFilter($value);
            }

            return $params;
        }

        if (is_object($params)) {
            foreach (get_object_vars($params) as $property => $value) {
                $params->$property = $this->xssFilter($value);
            }

            return $params;
        }

        return $params;
    }

    private function sanitizeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @throws ReflectionException|DiException
     */
    private function updateDebugger(string $viewFile): void
    {
        $routesCell = $this->debugger->getStoreCell(Debugger::ROUTES);
        $currentData = current($routesCell)[LogLevel::INFO] ?? [];
        $additionalData = ['View' => request()->getCurrentModule() . '/Views/' . $viewFile];
        $mergedData = array_merge($currentData, $additionalData);
        $this->debugger->clearStoreCell(Debugger::ROUTES);
        $this->debugger->addToStoreCell(Debugger::ROUTES, LogLevel::INFO, $mergedData);
    }
}
