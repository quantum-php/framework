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
use Quantum\Config\Traits\WriteTrait;
use Quantum\Config\Traits\ReadTrait;
use Dflydev\DotAccessData\Data;
use Quantum\App\App;

/**
 * Class Config
 *
 * The application config, built up while the app runs: load() at boot,
 * import() when a package is first used. section() gives a read-only part of it.
 *
 * @package Quantum\Config
 */
class Config implements ConfigInterface
{
    use ReadTrait;
    use TypedValuesTrait;
    use WriteTrait;

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

        $filePath = $baseDir . DS . 'shared' . DS
            . strtolower($setup->getPathPrefix() ?? '') . DS
            . $setup->getFilename() . '.php';

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }
}
