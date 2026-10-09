<?php

namespace Quantum\Tests\Unit\App\Stages\Console;

use Quantum\App\Stages\Console\ValidateCommandStage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\App\Enums\ExitCode;
use Exception;

class ValidateCommandStageTest extends ConsoleStageTestCase
{
    public function testValidateCommandStageAcceptsRegisteredCommand(): void
    {
        $application = new Application();
        $application->add(new Command('known:command'));

        $context = $this->createConsoleContext(['command' => 'known:command'], $application);

        (new ValidateCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
    }

    public function testValidateCommandStageRejectsUnknownCommand(): void
    {
        $context = $this->createConsoleContext(['command' => 'unknown:command']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `unknown:command` is not defined');

        (new ValidateCommandStage())->process($context);
    }

    public function testValidateCommandStageRejectsMissingCommandName(): void
    {
        $context = $this->createConsoleContext();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        (new ValidateCommandStage())->process($context);
    }
}
