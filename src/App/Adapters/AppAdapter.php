<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters;

use Quantum\App\Contracts\AppInterface;
use Quantum\App\AppContext;

/**
 * Class AppAdapter
 * @package Quantum\App
 */
abstract class AppAdapter implements AppInterface
{
    protected AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;
    }
}
