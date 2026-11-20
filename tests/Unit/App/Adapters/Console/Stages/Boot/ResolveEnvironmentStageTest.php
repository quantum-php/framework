<?php

namespace Quantum\Tests\Unit\App\Adapters\Console\Stages\Boot;

use Quantum\Tests\Unit\App\Adapters\Console\Stages\ConsoleStageTestCase;
use Quantum\App\Adapters\Console\Stages\Boot\ResolveEnvironmentStage;
use Quantum\Environment\Exceptions\EnvException;
use Dotenv\Exception\InvalidFileException;
use Symfony\Component\Console\Application;
use Quantum\Console\CliCommand;

class ResolveEnvironmentStageTest extends ConsoleStageTestCase
{
    public function testResolveEnvironmentStageDoesNothingWhenTheEnvFileLoads(): void
    {
        $context = $this->createConsoleContext(['command' => 'needs:env'], $this->createApplication());

        (new ResolveEnvironmentStage())->process($context);

        $this->assertFalse($context->isStopped());
    }

    /**
     * @dataProvider commandsThatRunWithoutTheEnvironment
     */
    public function testResolveEnvironmentStageStopsTheBootWhenTheEnvFileIsMissing(string $commandName): void
    {
        $context = $this->createConsoleContext(['command' => $commandName], $this->createApplication());

        $this->withoutEnvironmentFile(function () use ($context): void {
            (new ResolveEnvironmentStage())->process($context);
        });

        $this->assertTrue($context->isStopped());
    }

    /**
     * @dataProvider commandsThatRunWithoutTheEnvironment
     */
    public function testResolveEnvironmentStageStopsTheBootWhenTheEnvFileIsMalformed(string $commandName): void
    {
        $context = $this->createConsoleContext(['command' => $commandName], $this->createApplication());

        $this->withEnvironmentFileContent("APP_NAME=two words here\n", function () use ($context): void {
            (new ResolveEnvironmentStage())->process($context);
        });

        $this->assertTrue($context->isStopped());
    }

    /**
     * @dataProvider inputsThatNeedTheEnvironment
     * @param array<string, string> $input
     */
    public function testResolveEnvironmentStageThrowsTheEnvExceptionWhenTheEnvFileIsMissing(array $input): void
    {
        $context = $this->createConsoleContext($input, $this->createApplication());

        $this->withoutEnvironmentFile(function () use ($context): void {
            try {
                (new ResolveEnvironmentStage())->process($context);

                $this->fail('The command needs the environment, so the boot must fail.');
            } catch (EnvException $exception) {
                $this->assertFalse($context->isStopped());
            }
        });
    }

    /**
     * @dataProvider inputsThatNeedTheEnvironment
     * @param array<string, string> $input
     */
    public function testResolveEnvironmentStageThrowsTheParseExceptionWhenTheEnvFileIsMalformed(array $input): void
    {
        $context = $this->createConsoleContext($input, $this->createApplication());

        $this->withEnvironmentFileContent("APP_NAME=two words here\n", function () use ($context): void {
            try {
                (new ResolveEnvironmentStage())->process($context);

                $this->fail('The command needs the environment, so the boot must fail.');
            } catch (InvalidFileException $exception) {
                $this->assertFalse($context->isStopped());
            }
        });
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commandsThatRunWithoutTheEnvironment(): array
    {
        return [
            'a CliCommand that does not require the environment' => ['no:env'],
            'list' => ['list'],
            'help' => ['help'],
        ];
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function inputsThatNeedTheEnvironment(): array
    {
        return [
            'a CliCommand that requires the environment' => [['command' => 'needs:env']],
            'an unknown command' => [['command' => 'unknown:command']],
            'no command' => [[]],
        ];
    }

    /**
     * An application with a CliCommand that requires the environment and one that does not
     */
    private function createApplication(): Application
    {
        $application = new Application();

        foreach (['needs:env' => true, 'no:env' => false] as $commandName => $requiresEnvironment) {
            $application->addCommand(new class ($commandName, $requiresEnvironment) extends CliCommand {
                public function __construct(string $commandName, bool $requiresEnvironment)
                {
                    $this->name = $commandName;
                    $this->requiresEnvironment = $requiresEnvironment;

                    parent::__construct();
                }

                public function exec(): void
                {
                }
            });
        }

        return $application;
    }

    /**
     * Hides the environment file of the test app for the duration of the callback
     */
    private function withoutEnvironmentFile(callable $test): void
    {
        $file = PROJECT_ROOT . DS . '.env.testing';
        $hidden = $file . '.hidden';

        $this->assertTrue(rename($file, $hidden));

        try {
            $test();
        } finally {
            rename($hidden, $file);
        }
    }

    /**
     * Replaces the content of the environment file of the test app for the duration of the callback
     */
    private function withEnvironmentFileContent(string $content, callable $test): void
    {
        $file = PROJECT_ROOT . DS . '.env.testing';
        $backup = $file . '.backup';

        $this->assertTrue(rename($file, $backup));

        try {
            $this->assertNotFalse(file_put_contents($file, $content));

            $test();
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            rename($backup, $file);
        }
    }
}
