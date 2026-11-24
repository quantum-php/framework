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
use Quantum\Config\Traits\ConfigFileTrait;
use Quantum\Config\Traits\WriteTrait;
use Quantum\Config\Traits\ReadTrait;
use Dflydev\DotAccessData\Data;

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
    use ConfigFileTrait;   // loads config files: import(), importIfExists()
    use ReadTrait;         // reads values: get(), has(), all(), section()
    use TypedValuesTrait;  // reads typed values: getBool(), getInt()
    use WriteTrait;        // changes values: set(), delete(), flush()

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
}
