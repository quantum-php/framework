<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Config\Contracts\ConfigInterface;
use Quantum\Config\Traits\TypedValuesTrait;
use Dflydev\DotAccessData\Data;
use Quantum\App\App;

/**
 * Class Config
 * @package Quantum\Config
 */
class Config implements ConfigInterface
{
    use TypedValuesTrait;

    private ?Data $configs = null;

    /**
     * @inheritDoc
     * @throws ConfigException
     */
    public function load(Setup $setup): void
    {
        if ($this->configs !== null) {
            return;
        }

        $this->configs = new Data($this->loadConfig($setup));
    }

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
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        if ($this->configs instanceof Data && $this->configs->has($key)) {
            return $this->configs->get($key);
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function all(): ?Data
    {
        return $this->configs;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->configs instanceof Data && !empty($this->configs->has($key));
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value): void
    {
        if (!$this->configs) {
            $this->configs = new Data([$key => $value]);
        } else {
            $this->configs->set($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): void
    {
        $this->configs instanceof Data && $this->configs->remove($key);
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        $this->configs = null;
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
