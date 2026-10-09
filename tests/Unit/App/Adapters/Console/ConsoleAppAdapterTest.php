<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Symfony\Component\Console\Application;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Tracer\ErrorHandler;
use Quantum\Di\Di;
use Exception;
use Mockery;

class ConsoleAppAdapterTest extends AppTestCase
{
    private $consoleAppAdapter;

    public function setUp(): void
    {
        $applicationMock = Mockery::mock(Application::class)->makePartial();
        $applicationMock->shouldReceive('getName')->andReturn('Qt Console Application');
        $applicationMock->shouldReceive('run')->andReturn(0);

        $this->consoleAppAdapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $this->consoleAppAdapter
            ->shouldReceive('createApplication')
            ->andReturn($applicationMock);
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
    }

    public function testConsoleAppAdapterStartSuccessfully(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $result = $this->consoleAppAdapter->start();

        $this->assertEquals(0, $result);
    }

    public function testConsoleAppAdapterReturnsNonZeroExitCode(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $applicationMock = Mockery::mock(Application::class)->makePartial();
        $applicationMock->shouldReceive('getName')->andReturn('Qt Console Application');
        $applicationMock->shouldReceive('run')->andReturn(2);

        $consoleAppAdapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $consoleAppAdapter
            ->shouldReceive('createApplication')
            ->andReturn($applicationMock);

        $consoleAppAdapter->__construct($this->createContext());

        $result = $consoleAppAdapter->start();

        $this->assertEquals(2, $result);
    }

    public function testConsoleAppAdapterStartFails(): void
    {
        $_SERVER['argv'] = ['qt', 'unknown', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->expectException(Exception::class);

        $this->consoleAppAdapter->start();
    }

    public function testConsoleAppAdapterStartFailsWithoutCommandName(): void
    {
        $_SERVER['argv'] = ['qt'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        $this->consoleAppAdapter->start();
    }

    public function testConsoleAppAdapterStartRegistersCoreCommands(): void
    {
        $directory = PROJECT_ROOT . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Quantum\Console\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersAppCommands(): void
    {
        $directory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $this->assertSame(0, $this->startWithRealApplication($commandName));
            $this->assertSame($commandName, $GLOBALS['tmpCommandRan'] ?? null);
        });
    }

    public function testConsoleAppAdapterStartRegistersCoreCommandsBeforeAppCommands(): void
    {
        $coreDirectory = PROJECT_ROOT . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src' . DS . 'Console' . DS . 'Commands';
        $appDirectory = PROJECT_ROOT . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($coreDirectory, 'Quantum\Console\Commands', function (string $coreCommand) use ($appDirectory): void {
            $this->withTemporaryCommand($appDirectory, 'Shared\Commands', function (string $appCommand) use ($coreCommand): void {
                $application = new Application('Qt Console Application', 'test');
                $application->setAutoExit(false);
                $application->setCatchExceptions(false);

                $adapter = Mockery::mock(ConsoleAppAdapter::class)
                    ->shouldAllowMockingProtectedMethods()
                    ->makePartial();

                $adapter->shouldReceive('createApplication')->andReturn($application);

                $_SERVER['argv'] = ['qt', 'list', '--quiet'];

                $adapter->__construct($this->createContext());
                $adapter->start();

                $names = array_keys($application->all());

                $this->assertContains($coreCommand, $names);
                $this->assertContains($appCommand, $names);
                $this->assertLessThan(array_search($appCommand, $names, true), array_search($coreCommand, $names, true));
            });
        });
    }

    public function testConsoleAppAdapterBootsAllStagesForRegularCommands(): void
    {
        $_SERVER['argv'] = ['qt', 'list', '--quiet'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(Di::isRegistered(ErrorHandler::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testConsoleAppAdapterBootsReducedStagesForCoreEnvCommand(): void
    {
        $_SERVER['argv'] = ['qt', 'core:env'];

        $this->consoleAppAdapter->__construct($this->createContext());

        $this->assertFalse(Di::has(Environment::class));
        $this->assertFalse(Di::isRegistered(ErrorHandler::class));
        $this->assertFalse(config()->has('app'));
    }

    /**
     * Starts the adapter with a real Symfony Application (no auto exit, no exception catching),
     * so the registered command is really executed and its exit code comes from Symfony.
     */
    private function startWithRealApplication(string $commandName): ?int
    {
        $application = new Application('Qt Console Application', 'test');
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $adapter = Mockery::mock(ConsoleAppAdapter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $adapter->shouldReceive('createApplication')->andReturn($application);

        $_SERVER['argv'] = ['qt', $commandName, '--quiet'];

        $adapter->__construct($this->createContext());

        return $adapter->start();
    }

    /**
     * Creates a temporary command class in the given directory, loads it, runs the test
     * with the command name, and removes the file and any directories it had to create.
     * The command records that it ran in $GLOBALS['tmpCommandRan'].
     */
    private function withTemporaryCommand(string $directory, string $namespace, callable $test): void
    {
        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $className = 'TmpCommand' . $suffix;
        $commandName = 'tmp:command' . $suffix;
        $file = $directory . DS . $className . '.php';

        $missing = [];

        for ($dir = $directory; !is_dir($dir); $dir = dirname($dir)) {
            array_unshift($missing, $dir);
        }

        $command = <<<PHP
<?php

namespace {$namespace};

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = '{$commandName}';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    public function exec(): void
    {
        \$GLOBALS['tmpCommandRan'] = '{$commandName}';
    }
}
PHP;

        try {
            if ($missing !== []) {
                $this->assertTrue(mkdir($directory, 0777, true));
            }

            $this->assertNotFalse(file_put_contents($file, $command));

            require_once $file;

            $test($commandName);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            foreach (array_reverse($missing) as $dir) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }

            unset($GLOBALS['tmpCommandRan']);
        }
    }
}
