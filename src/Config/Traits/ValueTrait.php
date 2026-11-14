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
 * Trait ValueTrait
 * @package Quantum\Config
 */
trait ValueTrait
{
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
}
