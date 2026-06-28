<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Quantum\Module\ModuleManager;
use Quantum\Console\CliCommand;
use Exception;

/**
 * Class ModuleGenerateCommand
 * @package Quantum\Console
 */
class ModuleGenerateCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'module:generate';

    /**
     * Command description
     */
    protected ?string $description = 'Generate new module';

    /**
     * Command arguments
     * @var array<int, array<int|string, mixed>>
     */
    protected array $args = [
        ['module', 'required', 'The module name'],
    ];

    /**
     * Command help text
     */
    protected ?string $help = 'The command will create files for new module';

    /**
     * Command options
     */
    protected array $options = [
        ['yes', 'y', 'none', 'Module enabled status'],
        ['template', 't', 'optional', 'The module template', 'DefaultWeb'],
        ['with-assets', 'a', 'none', 'Install module will assets'],
    ];

    /**
     * Executes the command
     * @throws ExceptionInterface
     */
    public function exec(): void
    {
        try {
            $moduleName = $this->getArgument('module');

            $moduleManager = new ModuleManager(
                $moduleName,
                $this->getOption('template'),
                $this->getOption('yes'),
                $this->getOption('with-assets')
            );

            $moduleManager->writeContents();

            $moduleManager->addModuleConfig();

            $this->info($moduleName . ' module successfully created');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            exit(1);
        }
    }
}
