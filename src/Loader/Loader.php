<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Loader;

use Quantum\Loader\Exceptions\LoaderException;
use Quantum\App\App;

/**
 * Class Loader
 * @package Quantum\Loader
 */
class Loader
{
    /**
     * Current module
     */
    private ?string $module = null;

    /**
     * Environment
     */
    private ?string $pathPrefix = null;

    /**
     * File name
     */
    private ?string $fileName = null;

    /**
     * Hierarchical
     */
    private bool $hierarchical = false;

    /**
     * Exception message
     */
    private string $exceptionMessage = '';

    /**
     * Setups the loader
     */
    public function setup(Setup $setup): Loader
    {
        $this->hierarchical = $setup->getHierarchy();
        $this->module = $setup->getModule();
        $this->pathPrefix = $setup->getPathPrefix();
        $this->fileName = $setup->getFilename();
        $this->exceptionMessage = $setup->getExceptionMessage();

        return $this;
    }

    /**
     * Sets new value
     * @param mixed $value
     */
    public function set(string $property, $value): Loader
    {
        $this->$property = $value;
        return $this;
    }

    /**
     * Loads .php files from given directory
     */
    public function loadDir(string $dir): void
    {
        $files = glob($dir . DS . '*.php');

        if (!is_array($files)) {
            return;
        }

        foreach ($files as $filename) {
            require_once $filename;
        }
    }

    /**
     * Loads the content
     * @return mixed
     * @throws LoaderException
     */
    public function load()
    {
        return require $this->getFilePath();
    }

    /**
     * Checks if the setup points to valid file
     */
    public function fileExists(): bool
    {
        $filePath = $this->resolveFilePath();

        if (file_exists($filePath)) {
            return true;
        }

        if ($this->hierarchical) {
            $filePath = App::getBaseDir() . DS . 'shared' . DS . strtolower($this->pathPrefix ?? '') . DS . $this->fileName . '.php';
            return file_exists($filePath);
        }

        return false;
    }

    /**
     * Gets the file path
     * @throws LoaderException
     */
    public function getFilePath(): string
    {
        $filePath = $this->resolveFilePath();

        if (!file_exists($filePath)) {
            if ($this->hierarchical) {
                $filePath = App::getBaseDir() . DS . 'shared' . DS . strtolower($this->pathPrefix ?? '') . DS . $this->fileName . '.php';

                if (!file_exists($filePath)) {
                    throw new LoaderException(_message($this->exceptionMessage, $this->fileName ?? ''));
                }
            } else {
                throw new LoaderException(_message($this->exceptionMessage, $this->fileName ?? ''));
            }
        }

        return $filePath;
    }

    private function resolveFilePath(): string
    {
        $filePath = '';

        if ($this->module) {
            $filePath = modules_dir() . DS . $this->module . DS;
        }

        if ($this->pathPrefix) {
            $filePath .= $this->pathPrefix . DS;
        }

        return $filePath . ($this->fileName . '.php');
    }
}
