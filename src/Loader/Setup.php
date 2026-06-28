<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Loader;

use Quantum\Di\Exceptions\DiException;
use ReflectionException;

/**
 * Class Setup
 * @package Quantum\Loader
 */
class Setup
{
    protected bool $hierarchical;

    protected ?string $module;

    protected ?string $pathPrefix;

    protected ?string $fileName;

    protected string $exceptionMessage;

    /**
     * @throws DiException|ReflectionException
     */
    public function __construct(?string $pathPrefix = null, ?string $fileName = null, bool $hierarchical = true, ?string $module = null, ?string $exceptionMessage = null)
    {
        $this->pathPrefix = $pathPrefix;
        $this->fileName = $fileName;
        $this->hierarchical = $hierarchical;
        $this->module = $module ?: request()->getCurrentModule();
        $this->exceptionMessage = $exceptionMessage ?: 'File `' . $pathPrefix . DS . $fileName . '` not found!';
    }

    /**
     * Sets the path prefix
     */
    public function setPathPrefix(string $pathPrefix): Setup
    {
        $this->pathPrefix = $pathPrefix;
        return $this;
    }

    /**
     * Gets the path prefix
     */
    public function getPathPrefix(): ?string
    {
        return $this->pathPrefix;
    }

    /**
     * Set the filename
     */
    public function setFilename(string $fileName): Setup
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Gets the filename
     */
    public function getFilename(): ?string
    {
        return $this->fileName;
    }

    /**
     * Sets the module
     */
    public function setModule(string $module): Setup
    {
        $this->module = $module;
        return $this;
    }

    /**
     * Gets the module
     */
    public function getModule(): ?string
    {
        return $this->module;
    }

    /**
     * Sets the exception message
     */
    public function setExceptionMessage(string $exceptionMessage): Setup
    {
        $this->exceptionMessage = $exceptionMessage;
        return $this;
    }

    /**
     * Gets the exception message
     */
    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    /**
     * Set the hierarchy
     */
    public function setHierarchy(bool $hierarchy): Setup
    {
        $this->hierarchical = $hierarchy;
        return $this;
    }

    /**
     * Gets the hierarchy
     */
    public function getHierarchy(): bool
    {
        return $this->hierarchical;
    }
}
