<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Quantum\App\Stages\SetupErrorHandlerStage;
use Quantum\App\Stages\LoadEnvironmentStage;
use Symfony\Component\Console\Application;
use Quantum\App\Stages\LoadAppConfigStage;
use Quantum\App\Stages\LoadHelpersStage;
use Quantum\App\Traits\ConsoleAppTrait;
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
class ConsoleAppAdapter extends AppAdapter
{
    use ConsoleAppTrait;

    protected ArgvInput $input;

    protected ConsoleOutput $output;

    protected Application $application;

    public function __construct(AppContext $context)
    {
        parent::__construct($context);

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

    /**
    * @throws Exception
    */
    public function start(): ?int
    {
        $this->registerCoreCommands();
        $this->registerAppCommands();

        $this->validateCommand();

        $exitCode = $this->application->run($this->input, $this->output);

        return $exitCode ?: ExitCode::SUCCESS;
    }
}
