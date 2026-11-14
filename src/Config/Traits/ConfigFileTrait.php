<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Traits;

use Quantum\Config\Exceptions\ConfigException;
use Dflydev\DotAccessData\Data;
use Quantum\Config\Setup;
use Quantum\App\App;

/**
 * Trait ConfigFileTrait
 * @package Quantum\Config
 */
trait ConfigFileTrait
{
    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public function import(Setup $setup): void
    {
        $fileName = $setup->getFilename();

        if ($fileName && $this->has($fileName)) {
            throw ConfigException::configCollision($fileName);
        }

        $data = $this->loadConfig($setup);

        if (!$this->configs) {
            $this->configs = new Data([$fileName => $data]);
        } else {
            $this->configs->import([$fileName => $data]);
        }
    }

    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public function importIfExists(Setup $setup): bool
    {
        if ($this->resolveFilePath($setup) === null) {
            return false;
        }

        $this->import($setup);

        return true;
    }

    /**
     * @return array<string, mixed>
     * @throws ConfigException
     */
    private function loadConfig(Setup $setup): array
    {
        $filePath = $this->resolveFilePath($setup);

        if ($filePath === null) {
            throw new ConfigException(_message($setup->getExceptionMessage(), $setup->getFilename() ?? ''));
        }

        return require $filePath;
    }

    private function resolveFilePath(Setup $setup): ?string
    {
        $baseDir = App::getBaseDir();

        $filePath = $baseDir . DS;

        if ($setup->getModule()) {
            $filePath = modules_dir() . DS . $setup->getModule() . DS;
        }

        if ($setup->getPathPrefix()) {
            $filePath .= $setup->getPathPrefix() . DS;
        }

        $filePath .= $setup->getFilename() . '.php';

        if (file_exists($filePath)) {
            return $filePath;
        }

        if ($setup->getHierarchy()) {
            $filePath = $baseDir . DS . 'shared' . DS
                . strtolower($setup->getPathPrefix() ?? '') . DS
                . $setup->getFilename() . '.php';

            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        return null;
    }
}
