<?php

namespace Quantum\Tests\Unit\App\Stages\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Quantum\App\Stages\Console\RunCommandStage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\App\Enums\ExitCode;

class RunCommandStageTest extends ConsoleStageTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Symfony stores the verbosity of an earlier `--quiet` run in the process environment,
        // which would silence the output of every later run in the same test process.
        putenv('SHELL_VERBOSITY');
        unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
    }

    public function testRunCommandStageStoresSuccessExitCode(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:ok'], $this->createApplication('run:ok', 0));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
    }

    public function testRunCommandStageStoresNonZeroExitCode(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:fail'], $this->createApplication('run:fail', 3));

        (new RunCommandStage())->process($context);

        $this->assertSame(3, $context->getExitCode());
    }

    public function testRunCommandStageRunsTheCommandWithTheContextOutput(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:ok'], $this->createApplication('run:ok', 0));

        (new RunCommandStage())->process($context);

        $this->assertStringContainsString('hello from run:ok', $context->getOutput()->fetch());
    }

    private function createApplication(string $commandName, int $exitCode): Application
    {
        $application = new Application();

        $application->add(
            (new Command($commandName))->setCode(function (InputInterface $input, OutputInterface $output) use ($commandName, $exitCode): int {
                $output->writeln('hello from ' . $commandName);

                return $exitCode;
            })
        );

        return $application;
    }
}
