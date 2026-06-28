<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Storage\Factories\FileSystemFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Console\CliCommand;
use ReflectionException;
use Quantum\App\App;

/**
 * Class EnvCommand
 * @package Quantum\Console
 */
class EnvCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'core:env';

    /**
     * Command description
     */
    protected ?string $description = 'Generates new .env file';

    /**
     * Command help text
     */
    protected ?string $help = 'The command will generate new .env file from .env.example';

    /**
     * Command options
     */
    protected array $options = [
        ['yes', 'y', 'none', 'Acceptance of the confirmation'],
    ];

    /**
     * Executes the command and creates new .env file
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function exec(): void
    {
        $fs = FileSystemFactory::get();

        if (!$fs->exists(App::getBaseDir() . DS . '.env.example')) {
            $this->error('.env.example file not found');
        }

        if ($fs->exists('.env') && !$this->getOption('yes') && !$this->confirm('The operation will overwrite values of the existing .env and will create new one from .env.example. Continue?')) {
            $this->info('Operation was canceled!');
            return;
        }

        $fs->copy(
            App::getBaseDir() . DS . '.env.example',
            App::getBaseDir() . DS . '.env'
        );

        $this->info('.env is successfully generated');
    }
}
