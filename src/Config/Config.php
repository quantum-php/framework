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
use Quantum\Config\Traits\ValueTrait;
use Dflydev\DotAccessData\Data;

/**
 * Class Config
 * @package Quantum\Config
 */
class Config implements ConfigInterface
{
    use ConfigFileTrait;
    use ValueTrait;
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
}
