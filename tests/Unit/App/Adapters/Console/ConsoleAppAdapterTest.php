<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Symfony\Component\Console\Input\InputInterface;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\Environment\Exceptions\EnvException;
use Quantum\Tests\Helpers\NonExitingApplication;
use Quantum\Tests\Helpers\UsesTemporaryAppRoot;
use Symfony\Component\Console\Input\ArgvInput;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Symfony\Component\Console\Application;
use Dotenv\Exception\InvalidFileException;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\App\Enums\AppType;
use Quantum\App\AppContext;
use Quantum\App\Pipeline;
use ReflectionProperty;
use RuntimeException;
use Quantum\Di\Di;
use ParseError;
use Exception;
use Mockery;

class ConsoleAppAdapterTest extends AppTestCase
{
    use UsesTemporaryAppRoot;

    public function setUp(): void
    {
        // AppTestCase::setUp() boots a web app, which these tests must not have
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
        $this->removeTemporaryAppRoot();
    }

    public function testConsoleAppAdapterStartSuccessfully(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $adapter = $this->createAdapter();

        $result = $adapter->start();

        $this->assertEquals(0, $result);
    }

    public function testConsoleAppAdapterReturnsNonZeroExitCode(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $applicationMock = Mockery::mock(Application::class)->makePartial();
        $applicationMock->shouldReceive('getName')->andReturn('Qt Console Application');
        $applicationMock->shouldReceive('run')->andReturn(2);

        $adapter = $this->createAdapter($applicationMock);

        $result = $adapter->start();

        $this->assertEquals(2, $result);
    }

    public function testConsoleAppAdapterStartFails(): void
    {
        $_SERVER['argv'] = ['qt', 'unknown', '--quiet'];

        $adapter = $this->createAdapter();

        $this->expectException(Exception::class);

        $adapter->start();
    }

    public function testConsoleAppAdapterStartFailsWithoutCommandName(): void
    {
        $_SERVER['argv'] = ['qt'];

        $adapter = $this->createAdapter();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        $adapter->start();
    }

    public function testConsoleAppAdapterStartRegistersCoreCommands(): void
    {
        $directory = $this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Quantum\Console\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersAppCommands(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersCoreCommandsBeforeAppCommands(): void
    {
        $coreDirectory = $this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';
        $appDirectory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($coreDirectory, 'Quantum\Console\Commands', function (string $coreCommand) use ($appDirectory): void {
            $this->withTemporaryCommand($appDirectory, 'Shared\Commands', function (string $appCommand) use ($coreCommand): void {
                $application = new NonExitingApplication('Qt Console Application', 'test');

                $_SERVER['argv'] = ['qt', 'list', '--quiet'];

                $this->createAdapter($application)->start();

                $names = array_keys($application->all());

                $this->assertContains($coreCommand, $names);
                $this->assertContains($appCommand, $names);
                $this->assertLessThan(array_search($appCommand, $names, true), array_search($coreCommand, $names, true));
            });
        });
    }

    public function testConsoleAppAdapterStartLetsAppCommandOverrideCoreCommandWithSameName(): void
    {
        $coreDirectory = $this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';
        $appDirectory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($coreDirectory, 'Quantum\Console\Commands', function (string $sharedName) use ($appDirectory): void {
            $this->withTemporaryCommand($appDirectory, 'Shared\Commands', function (string $commandName): void {
                $this->assertSame(0, $this->startWithRealApplication($commandName));
                $this->assertSame('Shared\Commands', $GLOBALS['tmpCommandSource'] ?? null);
            }, $sharedName);
        });
    }

    public function testConsoleAppAdapterStartWorksWithoutAppCommandsDirectory(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';
        $hidden = $this->appBaseDir() . DS . 'shared' . DS . 'Commands_hidden';

        $this->assertTrue(rename($directory, $hidden));

        try {
            $this->assertSame(0, $this->startWithRealApplication('list'));
        } finally {
            rename($hidden, $directory);
        }
    }

    public function testConsoleAppAdapterStartRegistersOnlyInstantiableCommands(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $before = $this->registeredCommandNames();

        $plain = 'TmpPlain' . substr(md5(uniqid('', true)), 0, 8);
        $abstract = 'TmpAbstract' . substr(md5(uniqid('', true)), 0, 8);

        $plainCode = "<?php\n\nnamespace Shared\\Commands;\n\nclass {$plain}\n{\n}\n";
        $abstractCode = "<?php\n\nnamespace Shared\\Commands;\n\nabstract class {$abstract} extends \\Quantum\\Console\\CliCommand\n{\n}\n";

        $this->withTemporaryFile($directory, $plain, $plainCode, function () use ($directory, $abstract, $abstractCode, $before): void {
            $this->withTemporaryFile($directory, $abstract, $abstractCode, function () use ($before): void {
                $this->assertSame($before, $this->registeredCommandNames());
            });
        });
    }

    public function testConsoleAppAdapterBootsAllStagesForRegularCommands(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->createAdapter();

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(Di::isRegistered(ErrorHandler::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterBootsAllStagesForCoreEnvCommandWhenTheEnvFileExists(): void
    {
        $_SERVER['argv'] = ['qt', 'core:env'];

        $this->createAdapter();

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(Di::isRegistered(ErrorHandler::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterStartDispatchesTheCommandEventsAroundTheCommandWithTheConsoleContext(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $adapter = $this->createAdapter(new NonExitingApplication('Qt Console Application', 'test'));

        $events = [];

        foreach (['console.command.before', 'console.command.after'] as $name) {
            event()->listen($name, function (array $payload) use (&$events, $name): void {
                $events[] = [$name, get_class($payload['context'])];
            });
        }

        $this->assertSame(0, $adapter->start());

        $this->assertSame([
            ['console.command.before', ConsoleContext::class],
            ['console.command.after', ConsoleContext::class],
        ], $events);
    }

    public function testConsoleAppAdapterStartDispatchesNoAfterEventWhenTheCommandIsUnknown(): void
    {
        $_SERVER['argv'] = ['qt', 'unknown:command', '--quiet'];

        $adapter = $this->createAdapter();

        $events = [];

        foreach (['console.command.before', 'console.command.after'] as $name) {
            event()->listen($name, function () use (&$events, $name): void {
                $events[] = $name;
            });
        }

        try {
            $adapter->start();
            $this->fail('Expected the unknown command to be rejected.');
        } catch (Exception $exception) {
            $this->assertSame(['console.command.before'], $events);
        }
    }

    public function testConsoleAppAdapterConstructorDoesNotBoot(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        new ConsoleAppAdapter($this->createContext(AppType::CONSOLE));

        $this->assertFalse(Di::isRegistered(Application::class));
        $this->assertFalse(Di::has(Environment::class));
    }

    public function testConsoleAppAdapterBootRunsTheBootStages(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $adapter = new ConsoleAppAdapter($this->createContext(AppType::CONSOLE));
        $adapter->boot();

        $this->assertTrue(Di::isRegistered(Application::class));
        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterRegistersTheConsoleObjectsInTheContainerAfterASuccessfulBoot(): void
    {
        $_SERVER['argv'] = ['qt', 'core:env', '--yes'];

        (new ConsoleAppAdapter($this->createContext(AppType::CONSOLE)))->boot();

        $this->assertTrue(Di::isRegistered(Application::class));
        $this->assertTrue(Di::isRegistered(OutputInterface::class));
        $this->assertInstanceOf(ArgvInput::class, Di::get(InputInterface::class));
        $this->assertSame('core:env', Di::get(InputInterface::class)->getFirstArgument());
    }

    public function testConsoleAppAdapterRegistersTheConsoleObjectsInTheContainerWhenTheBootStopsBeforeTheEnvironment(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withoutEnvironmentFile(function (): void {
            (new ConsoleAppAdapter($this->createContext(AppType::CONSOLE)))->boot();

            $this->assertTrue(Di::isRegistered(Application::class));
            $this->assertTrue(Di::isRegistered(OutputInterface::class));
            $this->assertInstanceOf(ArgvInput::class, Di::get(InputInterface::class));
            $this->assertSame('list', Di::get(InputInterface::class)->getFirstArgument());
        });
    }

    public function testConsoleAppAdapterBootStopsBeforeTheEnvironmentForListWhenTheEnvFileIsMissing(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withoutEnvironmentFile(function (): void {
            $this->createAdapter();

            $this->assertFalse(Di::has(Environment::class));
            $this->assertFalse(Di::isRegistered(ErrorHandler::class));
            $this->assertFalse(config()->has('app'));
        });
    }

    public function testConsoleAppAdapterDoesNotMakeTheEnvironmentMutableAfterAFailedBoot(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withoutEnvironmentFile(function (): void {
            $this->createAdapter();

            $this->assertFalse(Di::has(Environment::class));
        });
    }

    public function testConsoleAppAdapterNamesTheApplicationUnknownWhenTheAppNameIsEmpty(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $context = $this->createContext(AppType::CONSOLE);

        (new Pipeline([new LoadHelpersStage()]))->run($context);

        config()->set('app', ['name' => null, 'version' => null]);

        $adapter = $this->createAdapter(null, $context);
        $adapter->start();

        $this->assertSame('UNKNOWN', Di::get(Application::class)->getName());
        $this->assertSame('UNKNOWN', Di::get(Application::class)->getVersion());
    }

    public function testConsoleAppAdapterKeepsAnEmptyAppNameAndVersionFromTheConfig(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $context = $this->createContext(AppType::CONSOLE);

        (new Pipeline([new LoadHelpersStage()]))->run($context);

        config()->set('app', ['name' => '', 'version' => '0']);

        $adapter = $this->createAdapter(null, $context);
        $adapter->start();

        $this->assertSame('', Di::get(Application::class)->getName());
        $this->assertSame('0', Di::get(Application::class)->getVersion());
    }

    public function testConsoleAppAdapterBootsAllStagesForUnknownCommand(): void
    {
        $_SERVER['argv'] = ['qt', 'unknown:command', '--quiet'];

        $this->createAdapter();

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(Di::isRegistered(ErrorHandler::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterDoesNotMakeTheEnvironmentMutableAfterTheFullBoot(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->createAdapter();

        $property = new ReflectionProperty(Environment::class, 'isMutable');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue(environment()));
    }

    public function testConsoleAppAdapterNamesTheApplicationFromTheAppConfigAfterTheFullBoot(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->createAdapter()->start();

        $this->assertSame(config()->get('app.name'), Di::get(Application::class)->getName());
        $this->assertSame(config()->get('app.version'), Di::get(Application::class)->getVersion());
        $this->assertNotSame('UNKNOWN', Di::get(Application::class)->getName());
    }

    public function testConsoleAppAdapterNamesTheApplicationUnknownWhenTheEnvFileIsMissing(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withoutEnvironmentFile(function (): void {
            $this->createAdapter()->start();

            $this->assertSame('UNKNOWN', Di::get(Application::class)->getName());
            $this->assertSame('UNKNOWN', Di::get(Application::class)->getVersion());
        });
    }

    public function testConsoleAppAdapterBootFailsWhenTheErrorHandlerCannotBeSetUp(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withFileContent($this->appBaseDir() . DS . 'shared' . DS . 'config' . DS . 'logging.php', "<?php\n\nreturn [\n", function (): void {
            $this->expectException(ParseError::class);

            $this->createAdapter();
        });
    }

    public function testConsoleAppAdapterBootRejectsACommandThatRequiresTheEnvironmentWhenTheEnvFileIsMissing(): void
    {
        $this->withTemporaryCommand($this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands', 'Quantum\Console\Commands', function (string $commandName): void {
            $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

            $this->withoutEnvironmentFile(function (): void {
                $this->expectException(EnvException::class);

                $this->createAdapter();
            });
        });
    }

    public function testConsoleAppAdapterStartRunsACoreCommandThatDoesNotRequireTheEnvironmentWhenTheEnvFileIsMissing(): void
    {
        $this->withTemporaryCommand($this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands', 'Quantum\Console\Commands', function (string $commandName): void {
            $this->withoutEnvironmentFile(function () use ($commandName): void {
                $this->assertSame(0, $this->startWithRealApplication($commandName));
                $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
            });
        }, null, false);
    }

    public function testConsoleAppAdapterBootRejectsAnAppCommandThatDoesNotRequireTheEnvironmentWhenTheEnvFileIsMissing(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

            $this->withoutEnvironmentFile(function (): void {
                $this->expectException(EnvException::class);

                $this->createAdapter();
            });
        }, null, false);
    }

    public function testConsoleAppAdapterStartRunsAnAppCommandWhoseConstructorUsesTheEnvironment(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';
        $className = 'TmpEnvCommand' . substr(md5(uniqid('', true)), 0, 8);
        $commandName = 'tmp:env-constructor';

        $command = <<<PHP
<?php

namespace Shared\Commands;

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = '{$commandName}';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    private string \$appKey;

    public function __construct()
    {
        parent::__construct();

        \$this->appKey = (string) env('APP_KEY');
    }

    public function exec(): void
    {
        \$GLOBALS['tmpCommandRan'] = \$this->appKey;
    }
}
PHP;

        $this->withTemporaryFile($directory, $className, $command, function () use ($commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame(env('APP_KEY'), $GLOBALS['tmpCommandRan'] ?? null);
            $this->assertNotEmpty($GLOBALS['tmpCommandRan']);
        });
    }

    public function testConsoleAppAdapterStartRunsListWhenTheEnvFileIsMissing(): void
    {
        $this->withoutEnvironmentFile(function (): void {
            $this->assertSame(0, $this->startWithRealApplication('list'));
        });
    }

    public function testConsoleAppAdapterStartRunsACoreCommandThatDoesNotRequireTheEnvironmentWhenTheEnvFileIsMalformed(): void
    {
        $this->withTemporaryCommand($this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands', 'Quantum\Console\Commands', function (string $commandName): void {
            $this->withEnvironmentFileContent("APP_NAME=two words here\n", function () use ($commandName): void {
                $adapter = $this->bootRealAdapter($commandName);

                $this->assertSame(0, $adapter->start());
                $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
            });
        }, null, false);
    }

    public function testConsoleAppAdapterStartRunsACommandThatDoesNotRequireTheEnvironmentWhenTheEnvFileIsEmpty(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $this->withEnvironmentFileContent('', function () use ($commandName): void {
                $adapter = $this->bootRealAdapter($commandName);

                $this->assertSame(0, $adapter->start());
                $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
            });
        }, null, false);
    }

    public function testConsoleAppAdapterBootRejectsACommandThatRequiresTheEnvironmentWhenTheEnvFileIsMalformed(): void
    {
        $this->withTemporaryCommand($this->appBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands', 'Quantum\Console\Commands', function (string $commandName): void {
            $this->withEnvironmentFileContent("APP_NAME=two words here\n", function () use ($commandName): void {
                try {
                    $this->bootRealAdapter($commandName);
                    $this->fail('The command must not run when the environment file is malformed.');
                } catch (InvalidFileException $exception) {
                    $this->assertArrayNotHasKey('tmpCommandRan', $GLOBALS);
                }
            });
        });
    }

    public function testConsoleAppAdapterBootRejectsAnAppCommandThatDoesNotRequireTheEnvironmentWhenTheEnvFileIsMalformed(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $this->withEnvironmentFileContent("APP_NAME=two words here\n", function () use ($commandName): void {
                $this->expectException(InvalidFileException::class);

                $this->bootRealAdapter($commandName);
            });
        }, null, false);
    }

    public function testConsoleAppAdapterStartRunsListWhenTheEnvFileIsMalformed(): void
    {
        $this->withEnvironmentFileContent("APP_NAME=two words here\n", function (): void {
            $this->assertSame(0, $this->bootRealAdapter('list')->start());
        });
    }

    public function testConsoleAppAdapterBootReportsTheEnvFileExceptionForAnUnknownCommandWhenTheEnvFileIsMalformed(): void
    {
        $this->withEnvironmentFileContent("APP_NAME=two words here\n", function (): void {
            $this->expectException(InvalidFileException::class);

            $this->bootRealAdapter('unknown:command');
        });
    }

    public function testConsoleAppAdapterBootReportsTheEnvFileExceptionForAnUnknownCommandWhenTheEnvFileIsMissing(): void
    {
        $this->withoutEnvironmentFile(function (): void {
            $this->expectException(EnvException::class);

            $this->bootRealAdapter('unknown:command');
        });
    }

    public function testConsoleAppAdapterBootReportsTheEnvFileExceptionWithoutACommandWhenTheEnvFileIsMissing(): void
    {
        $_SERVER['argv'] = ['qt'];

        $this->withoutEnvironmentFile(function (): void {
            $this->expectException(EnvException::class);

            $this->createAdapter();
        });
    }

    public function testConsoleAppAdapterStartRunsListWhenTheEnvFileIsMissingAndAnAppCommandCannotBeCreated(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withBrokenCommand($directory, function (): void {
            $this->withoutEnvironmentFile(function (): void {
                $this->assertSame(0, $this->bootRealAdapter('list')->start());
            });
        });
    }

    public function testConsoleAppAdapterBootReportsTheCommandFailureWhenAnAppCommandCannotBeCreated(): void
    {
        $directory = $this->appBaseDir() . DS . 'shared' . DS . 'Commands';

        $this->withBrokenCommand($directory, function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('The command cannot be created');

            $this->bootRealAdapter('list');
        });
    }

    public function testConsoleAppAdapterBootFailsWhenAnAppHelperFileCannotBeLoaded(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->withBrokenAppHelper(function (): void {
            $this->expectException(ParseError::class);

            $this->createAdapter();
        });
    }

    /**
     * Puts an app helper file with a syntax error into the test app for the duration of the callback
     */
    private function withBrokenAppHelper(callable $test): void
    {
        $directory = $this->appBaseDir() . DS . 'helpers';
        $createdDirectory = !is_dir($directory);
        $file = $directory . DS . 'tmp_broken_helper_' . substr(md5(uniqid('', true)), 0, 8) . '.php';

        try {
            if ($createdDirectory) {
                $this->assertTrue(mkdir($directory));
            }

            $this->assertNotFalse(file_put_contents($file, "<?php\n\nfunction broken( {\n"));

            $test();
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            if ($createdDirectory && is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    /**
     * Replaces the content of the environment file of the test app for the duration of the callback
     */
    private function withEnvironmentFileContent(string $content, callable $test): void
    {
        $this->withFileContent($this->appBaseDir() . DS . '.env.testing', $content, $test);
    }

    /**
     * Replaces the content of a file of the test app for the duration of the callback
     */
    private function withFileContent(string $file, string $content, callable $test): void
    {
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

    /**
     * Creates and boots the adapter on a fresh context (or the given one) with a test Application in the container:
     * the given one, or one that does not exit and is built, and named, when start() first fetches it
     */
    private function createAdapter(?Application $application = null, ?AppContext $context = null): ConsoleAppAdapter
    {
        $context ??= $this->createContext(AppType::CONSOLE);

        if ($application !== null) {
            Di::set(Application::class, $application);
        } else {
            Di::register(NonExitingApplication::class, Application::class);
        }

        $adapter = new ConsoleAppAdapter($context);
        $adapter->boot();

        return $adapter;
    }

    /**
     * Boots the adapter for the command, so start() can be asserted on
     */
    private function bootRealAdapter(string $commandName): ConsoleAppAdapter
    {
        $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

        return $this->createAdapter();
    }

    /**
     * Puts an app command whose constructor throws into the directory for the duration of the test
     */
    private function withBrokenCommand(string $directory, callable $test): void
    {
        $className = 'TmpBrokenCommand' . substr(md5(uniqid('', true)), 0, 8);

        $command = <<<PHP
<?php

namespace Shared\Commands;

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = 'tmp:broken';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    public function __construct()
    {
        throw new \RuntimeException('The command cannot be created');
    }

    public function exec(): void
    {
    }
}
PHP;

        $this->withTemporaryFile($directory, $className, $command, $test);
    }

    /**
     * Hides the environment file of the test app for the duration of the callback
     */
    private function withoutEnvironmentFile(callable $test): void
    {
        $file = $this->appBaseDir() . DS . '.env.testing';
        $hidden = $file . '.hidden';

        $this->assertTrue(rename($file, $hidden));

        try {
            $test();
        } finally {
            rename($hidden, $file);
        }
    }

    /**
     * Starts the adapter with a real Symfony Application (no auto exit, no exception catching),
     * so the registered command is really executed and its exit code comes from Symfony.
     */
    private function startWithRealApplication(string $commandName): ?int
    {
        $application = new NonExitingApplication('Qt Console Application', 'test');

        $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

        return $this->createAdapter($application)->start();
    }

    /**
     * Returns the names of the commands that start() leaves registered on a real Symfony Application.
     * @return array<int, string>
     */
    private function registeredCommandNames(): array
    {
        $application = new NonExitingApplication('Qt Console Application', 'test');

        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->createAdapter($application)->start();

        return array_keys($application->all());
    }

    /**
     * Creates a temporary command class in the given directory, loads it, runs the test
     * with the command name, and removes the file and any directories it had to create.
     * The command records that it ran, and from which namespace, in $GLOBALS['tmpCommandRan'] and
     * $GLOBALS['tmpCommandSource']. A fixed command name creates two commands with the same name,
     * and the command can declare that it does not require the environment.
     */
    private function withTemporaryCommand(string $directory, string $namespace, callable $test, ?string $name = null, bool $requiresEnvironment = true): void
    {
        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $className = 'TmpCommand' . $suffix;
        $commandName = $name ?? 'tmp:command' . $suffix;
        $requires = $requiresEnvironment ? 'true' : 'false';

        $command = <<<PHP
<?php

namespace {$namespace};

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = '{$commandName}';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    protected bool \$requiresEnvironment = {$requires};

    public function exec(): void
    {
        \$GLOBALS['tmpCommandRan'] = '{$commandName}';
        \$GLOBALS['tmpCommandSource'] = '{$namespace}';
    }
}
PHP;

        $this->withTemporaryFile($directory, $className, $command, function () use ($test, $commandName): void {
            $test($commandName);
        });
    }

    /**
     * Writes a temporary PHP file named after the class into the directory, loads it, runs the test,
     * and removes the file and any directories it had to create.
     */
    private function withTemporaryFile(string $directory, string $className, string $code, callable $test): void
    {
        $file = $directory . DS . $className . '.php';

        $missing = [];

        for ($dir = $directory; !is_dir($dir); $dir = dirname($dir)) {
            array_unshift($missing, $dir);
        }

        try {
            if ($missing !== []) {
                $this->assertTrue(mkdir($directory, 0777, true));
            }

            $this->assertNotFalse(file_put_contents($file, $code));

            require_once $file;

            $test();
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            foreach (array_reverse($missing) as $dir) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }

            unset($GLOBALS['tmpCommandRan'], $GLOBALS['tmpCommandSource']);
        }
    }
}
