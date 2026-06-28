<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Symfony\Component\Console\Exception\ExceptionInterface;
use Quantum\Environment\Exceptions\EnvException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Console\CliCommand;
use ReflectionException;
use RuntimeException;

/**
 * Class InstallToolkitCommand
 * @package Quantum\Console
 */
class InstallToolkitCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'install:toolkit';

    /**
     * Command description
     */
    protected ?string $description = 'Installs toolkit';

    /**
     * Command arguments
     */
    protected array $args = [
        ['username', 'required', 'The username for basic auth'],
        ['password', 'required', 'The password for basic auth'],
    ];

    /**
     * Command help text
     */
    protected ?string $help = 'The command will install Toolkit and its assets into your project';

    /**
     * Command name to generate modules
     */
    public const COMMAND_CREATE_MODULE = 'module:generate';

    /**
     * Executes the command
     * @throws EnvException|ConfigException|BaseException|DiException|ExceptionInterface|ReflectionException
     */
    public function exec(): void
    {
        $name = $this->getArgument('username');

        $password = $this->getArgument('password');

        $env = environment();

        $env->setMutable(true);

        $env->updateRow('BASIC_AUTH_NAME', $name);

        $env->updateRow('BASIC_AUTH_PWD', $password);

        $this->runExternalCommand(self::COMMAND_CREATE_MODULE, [
            'module' => 'Toolkit',
            '--yes' => true,
            '--template' => 'Toolkit',
            '--with-assets' => true,
        ]);

        $this->info('Toolkit installed successfully');
    }

    /**
     * Runs an external command
     * @param array<string, mixed> $arguments
     * @throws ExceptionInterface
     */
    protected function runExternalCommand(string $commandName, array $arguments): void
    {
        $application = $this->getApplication();

        if ($application === null) {
            throw new RuntimeException('Application is not set.');
        }

        $command = $application->find($commandName);
        $command->run(new ArrayInput($arguments), new NullOutput());
    }
}
