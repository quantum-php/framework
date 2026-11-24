<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Config;

use Quantum\Config\Traits\TypedValuesTrait;
use Quantum\Config\Traits\ReadTrait;
use Dflydev\DotAccessData\Data;

/**
 * Class ConfigSection
 *
 * Read-only config values: a section of the config, such as `cache.redis`,
 * made by Config::section() or from an array.
 *
 * @package Quantum\Config
 */
final class ConfigSection
{
    use ReadTrait;
    use TypedValuesTrait;

    private ?Data $configs = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        if ($data !== []) {
            $this->configs = new Data($data);
        }
    }
}
