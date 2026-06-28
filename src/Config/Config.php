<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Config\Contracts\ConfigInterface;
use Quantum\Di\Exceptions\DiException;
use Dflydev\DotAccessData\Data;
use Quantum\Loader\Loader;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class Config
 * @package Quantum\Config
 */
class Config implements ConfigInterface
{
    private ?Data $configs = null;

    /**
     * @inheritDoc
     * @throws DiException|LoaderException|ReflectionException
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
     * @throws DiException
     * @throws ReflectionException|LoaderException
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
     */
    public function get(string $key, $default = null)
    {
        if ($this->configs && $this->configs->has($key)) {
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
        return $this->configs && !empty($this->configs->has($key));
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
        $this->configs && $this->configs->remove($key);
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
     * @throws DiException|LoaderException|ReflectionException
     */
    private function loadConfig(Setup $setup): array
    {
        if (!Di::isRegistered(Loader::class)) {
            Di::register(Loader::class);
        }

        return Di::get(Loader::class)->setup($setup)->load();
    }
}
