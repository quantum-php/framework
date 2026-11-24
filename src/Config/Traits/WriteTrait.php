<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config\Traits;

use Dflydev\DotAccessData\Data;

/**
 * Trait WriteTrait
 *
 * Changing the config values in $this->configs, used only by Config.
 *
 * @package Quantum\Config
 */
trait WriteTrait
{
    /**
     * @inheritDoc
     */
    public function set(string $key, $value): void
    {
        if (!$this->configs) {
            $this->configs = new Data();
        }

        $this->configs->set($key, $value);
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
}
