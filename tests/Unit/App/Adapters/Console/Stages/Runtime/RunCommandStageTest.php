<?php

namespace Quantum\Tests\Unit\App\Adapters\Console\Stages\Runtime;

use Quantum\Tests\Unit\App\Adapters\Console\Stages\ConsoleStageTestCase;
use Quantum\App\Adapters\Console\Stages\Runtime\RunCommandStage;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\App\Enums\ExitCode;
use Quantum\Console\CliCommand;
use Exception;

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

    public function testRunCommandStageRejectsUnknownCommand(): void
    {
        $context = $this->createConsoleContext(['command' => 'unknown:command'], $this->createApplication('run:ok', 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `unknown:command` is not defined');

        (new RunCommandStage())->process($context);
    }

    public function testRunCommandStageRejectsMissingCommandName(): void
    {
        $context = $this->createConsoleContext([], $this->createApplication('run:ok', 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        (new RunCommandStage())->process($context);
    }

    public function testRunCommandStageRunsNothingWhenTheCommandNameIsMissing(): void
    {
        $context = $this->createConsoleContext([], $this->createApplication('run:fail', 3));

        try {
            (new RunCommandStage())->process($context);
        } catch (Exception $e) {
            // expected, the command name is missing
        }

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertSame('', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRunsACliCommand(): void
    {
        $context = $this->createConsoleContext(['command' => 'cli:ok'], $this->createCliApplication('cli:ok'));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertStringContainsString('ran cli:ok', $context->getOutput()->fetch());
    }

    /**
     * An application with one CliCommand
     */
    private function createCliApplication(string $commandName): Application
    {
        $application = new Application();

        $application->addCommand(new class ($commandName) extends CliCommand {
            public function __construct(string $commandName)
            {
                $this->name = $commandName;

                parent::__construct();
            }

            public function exec(): void
            {
                $this->output('ran ' . $this->name);
            }
        });

        return $application;
    }

    private function createApplication(string $commandName, int $exitCode): Application
    {
        $application = new Application();

        $application->addCommand(
            (new Command($commandName))->setCode(function (InputInterface $input, OutputInterface $output) use ($commandName, $exitCode): int {
                $output->writeln('hello from ' . $commandName);

                return $exitCode;
            })
        );

        return $application;
    }
}
