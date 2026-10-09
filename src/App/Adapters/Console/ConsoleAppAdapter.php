<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Console;

use Quantum\App\Stages\Console\RegisterCoreCommandsStage;
use Quantum\App\Stages\Console\RegisterAppCommandsStage;
use Symfony\Component\Console\Output\ConsoleOutput;
use Quantum\App\Stages\Console\ValidateCommandStage;
use Quantum\App\Stages\Console\RunCommandStage;
use Symfony\Component\Console\Input\ArgvInput;
use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Symfony\Component\Console\Application;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Enums\ExitCode;
use Quantum\App\BootPipeline;
use Quantum\App\AppContext;
use Exception;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * Class ConsoleAppAdapter
 * @package Quantum\App
 */
class ConsoleAppAdapter implements AppInterface
{
    protected AppContext $context;

    protected ArgvInput $input;

    protected ConsoleOutput $output;

    protected Application $application;

    public function __construct(AppContext $context)
    {
        $this->context = $context;

        $this->input = new ArgvInput();
        $this->output = new ConsoleOutput();

        $commandName = $this->input->getFirstArgument();

        $stages = [
            new LoadHelpersStage(),
        ];

        if ($commandName !== 'core:env') {
            $stages[] = new LoadEnvironmentStage();
            $stages[] = new LoadAppConfigStage();
            $stages[] = new SetupErrorHandlerStage();
        }

        $pipeline = new BootPipeline($stages);
        $pipeline->run($this->context);

        if ($commandName !== 'core:env') {
            environment()->setMutable(true);
        }

        $this->application = $this->createApplication(
            config()->get('app.name', 'UNKNOWN'),
            config()->get('app.version', 'UNKNOWN')
        );
    }

    public function createApplication(string $name, string $version): Application
    {
        return new Application($name, $version);
    }

    /**
    * @throws Exception
    */
    public function start(): ?int
    {
        $context = new ConsoleContext($this->context, $this->application, $this->input, $this->output);

        $pipeline = new ConsolePipeline([
            new RegisterCoreCommandsStage(),
            new RegisterAppCommandsStage(),
            new ValidateCommandStage(),
            new RunCommandStage(),
        ]);

        $pipeline->run($context);

        return $context->getExitCode() ?: ExitCode::SUCCESS;
    }
}
