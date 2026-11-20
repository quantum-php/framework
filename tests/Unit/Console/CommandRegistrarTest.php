<?php

namespace Quantum\Tests\Unit\Console;

use Quantum\Tests\_root\shared\Commands\TestCommand;
use Symfony\Component\Console\Command\Command;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Symfony\Component\Console\Application;
use Quantum\Environment\Environment;
use Quantum\Console\CommandRegistrar;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Di\Di;

class CommandRegistrarTest extends AppTestCase
{
    private Application $application;

    private string $directory;

    private string $namespace = '\\Quantum\\Tests\\_root\\shared\\Commands\\';

    public function setUp(): void
    {
        parent::setUp();

        $this->application = new Application('Qt Console Application', 'test');

        $this->directory = base_dir() . DS . 'shared' . DS . 'Commands';
    }

    public function testCommandRegistrarAddsDiscoveredCommands(): void
    {
        $this->assertFalse($this->application->has('test:dummy'));

        CommandRegistrar::register($this->application, $this->directory, $this->namespace);

        $this->assertTrue($this->application->has('test:dummy'));
        $this->assertInstanceOf(TestCommand::class, $this->application->get('test:dummy'));
    }

    public function testCommandRegistrarIgnoresMissingDirectory(): void
    {
        $before = array_keys($this->application->all());

        CommandRegistrar::register($this->application, $this->directory . DS . 'missing', $this->namespace);

        $this->assertSame($before, array_keys($this->application->all()));
    }

    public function testCommandRegistrarIgnoresClassesOutsideTheNamespace(): void
    {
        $before = array_keys($this->application->all());

        CommandRegistrar::register($this->application, $this->directory, '\\Some\\Other\\Namespace\\');

        $this->assertSame($before, array_keys($this->application->all()));
    }

    public function testCommandRegistrarReplacesAnExistingCommandWithTheSameName(): void
    {
        $this->application->addCommand(new Command('test:dummy'));

        $this->assertNotInstanceOf(TestCommand::class, $this->application->get('test:dummy'));

        CommandRegistrar::register($this->application, $this->directory, $this->namespace);

        $this->assertInstanceOf(TestCommand::class, $this->application->get('test:dummy'));
    }

    public function testCommandRegistrarRaisesNoDeprecation(): void
    {
        $deprecations = [];

        set_error_handler(function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);

        try {
            CommandRegistrar::register($this->application, $this->directory, $this->namespace);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }

    public function testCommandRegistrarRegistersTheCoreCommandsWithoutLoadingTheEnvironmentOrAnyConfig(): void
    {
        $this->clearAppContext();

        (new LoadHelpersStage())->process($this->createContext());

        CommandRegistrar::register($this->application, dirname(__DIR__, 3) . DS . 'src' . DS . 'Console' . DS . 'Commands', '\\Quantum\\Console\\Commands\\');

        $this->assertTrue($this->application->has('core:env'));
        $this->assertFalse(Di::has(Environment::class));
        $this->assertNull(config()->all());
    }
}
