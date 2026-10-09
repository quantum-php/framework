<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web;

use Quantum\App\Stages\Request\HandleRouteNotFoundStage;
use Quantum\App\Stages\Request\HandlePreflightStage;
use Quantum\App\Stages\Request\DispatchRequestStage;
use Quantum\App\Stages\Request\PrepareRequestStage;
use Quantum\App\Stages\Request\SendResponseStage;
use Quantum\App\Stages\Request\ResolveRouteStage;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\InitDebuggerStage;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Stages\Boot\LoadModulesStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Stages\Boot\InitHttpStage;
use Quantum\App\Enums\ExitCode;
use Quantum\App\BootPipeline;
use Quantum\App\AppContext;

/**
 * Class WebAppAdapter
 * @package Quantum\App
 */
class WebAppAdapter implements AppInterface
{
    protected AppContext $context;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        $pipeline = new BootPipeline([
            new LoadHelpersStage(),
            new LoadEnvironmentStage(),
            new LoadAppConfigStage(),
            new SetupErrorHandlerStage(),
            new InitHttpStage(),
            new InitDebuggerStage(),
            new LoadModulesStage(),
        ]);

        $pipeline->run($this->context);
    }

    public function start(): ?int
    {
        $pipeline = new RequestPipeline([
            new HandlePreflightStage(),
            new ResolveRouteStage(),
            new HandleRouteNotFoundStage(),
            new PrepareRequestStage(),
            new DispatchRequestStage(),
            new SendResponseStage(),
        ]);

        $pipeline->run(new RequestContext($this->context));

        return ExitCode::SUCCESS;
    }
}
