<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Quantum\Console\Contracts\CommandInterface;
use Symfony\Component\Console\Command\Command;
use RuntimeException;

/**
 * Class CliCommand
 * @package Quantum\Console
 */
abstract class CliCommand extends Command implements CommandInterface
{
    /**
     * Console command name
     */
    protected ?string $name = null;

    /**
     * Console command description.
     */
    protected ?string $description = null;

    /**
     * Console command help text
     */
    protected ?string $help = null;

    /**
     * Console command input arguments
     * @example ['name', 'type', 'description']
     * @var array<int, array<int|string, mixed>>
     */
    protected array $args = [];

    /**
     * Console command options
     * @example ['name', 'shortcut', 'type', 'description', 'default']
     * @var array<int, array<int|string, mixed>>
     */
    protected array $options = [];

    protected ?InputInterface $input = null;

    protected ?OutputInterface $output = null;

    /**
     * CliCommand constructor.
     */
    public function __construct()
    {
        parent::__construct($this->name);

        if ($this->description !== null) {
            $this->setDescription($this->description);
        }

        if ($this->help !== null) {
            $this->setHelp($this->help);
        }
    }

    /**
     * Returns the argument value for a given argument name.
     * @return mixed|string
     */
    public function getArgument(?string $key = null)
    {
        return $this->resolveInput()->getArgument($key ?? '') ?? '';
    }

    /**
     * Returns the option value for a given option name.
     * @return mixed|string
     */
    public function getOption(?string $key = null)
    {
        return $this->resolveInput()->getOption($key ?? '') ?? '';
    }

    /**
     * Outputs the string to console
     */
    public function output(string $message): void
    {
        $this->resolveOutput()->writeln($message);
    }

    /**
     * Outputs the string to the console as info
     */
    public function info(string $message): void
    {
        $this->resolveOutput()->writeln("<info>$message</info>");
    }

    /**
     * Outputs the string to console as comment
     */
    public function comment(string $message): void
    {
        $this->resolveOutput()->writeln("<comment>$message</comment>");
    }

    /**
     * Outputs the string to console as question
     */
    public function question(string $message): void
    {
        $this->resolveOutput()->writeln("<question>$message</question>");
    }

    /**
     * Asks confirmation
     */
    public function confirm(string $message): bool
    {
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion($message . ' [y/N] ', false);

        return (bool) $helper->ask($this->resolveInput(), $this->resolveOutput(), $question);
    }

    /**
     * Outputs the string to the console as an error
     */
    public function error(string $message): void
    {
        $this->resolveOutput()->writeln("<error>$message</error>");
    }

    protected function resolveInput(): InputInterface
    {
        if ($this->input === null) {
            throw new RuntimeException('Input is not set.');
        }

        return $this->input;
    }

    protected function resolveOutput(): OutputInterface
    {
        if ($this->output === null) {
            throw new RuntimeException('Output is not set.');
        }

        return $this->output;
    }

    /**
     * Configures the current command.
     * @return void
     */
    protected function configure(): void
    {
        $this->setArguments();
        $this->setOptions();
    }

    /**
     * Executes the current command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->exec();

        return Command::SUCCESS;
    }

    /**
     * Sets command arguments
     */
    private function setArguments(): void
    {
        foreach ($this->args as $arg) {
            switch ($arg[1]) {
                case 'required':
                    $this->addArgument($arg[0], InputArgument::REQUIRED, $arg[2]);
                    break;
                case 'optional':
                    $this->addArgument($arg[0], InputArgument::OPTIONAL, $arg[2]);
                    break;
                case 'array':
                    $this->addArgument($arg[0], InputArgument::IS_ARRAY, $arg[2]);
                    break;
            }
        }
    }

    /**
     * Sets command options
     */
    private function setOptions(): void
    {
        foreach ($this->options as $option) {
            switch ($option[2]) {
                case 'none':
                    $this->addOption($option[0], $option[1], InputOption::VALUE_NONE, $option[3]);
                    break;
                case 'required':
                    $this->addOption($option[0], $option[1], InputOption::VALUE_REQUIRED, $option[3], $option[4] ?? '');
                    break;
                case 'optional':
                    $this->addOption($option[0], $option[1], InputOption::VALUE_OPTIONAL, $option[3], $option[4] ?? '');
                    break;
                case 'array':
                    $this->addOption($option[0], $option[1], InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $option[3], $option[4] ?? '');
                    break;
            }
        }
    }
}
