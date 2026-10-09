<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Boot;

use Quantum\App\Contracts\BootStageInterface;
use Quantum\App\AppContext;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Di\Di;

/**
 * Class InitHttpStage
 * @package Quantum\App
 */
class InitHttpStage implements BootStageInterface
{
    public const BEFORE = 'boot.http.before';

    public const AFTER = 'boot.http.after';

    public function process(AppContext $context): void
    {
        if (!Di::isRegistered(Request::class)) {
            Di::register(Request::class);
        }

        if (!Di::isRegistered(Response::class)) {
            Di::register(Response::class);
        }
    }
}
