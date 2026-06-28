<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Console\CliCommand;
use Exception;

/**
 * Class KeyGenerateCommand
 * @package Quantum\Console
 */
class KeyGenerateCommand extends CliCommand
{
    /**
     * Command name
     */
    protected ?string $name = 'core:key';

    /**
     * Command description
     */
    protected ?string $description = 'Generates and stores the application key';

    /**
     * Command help text
     */
    protected ?string $help = 'The command will generate APP_KEY and store in .env file';

    /**
     * Command options
     */
    protected array $options = [
        ['length', 'l', 'required', 'Length of the key', 32],
        ['yes', 'y', 'none', 'Acceptance of the confirmation'],
    ];

    /**
     * Executes the command and stores the generated key to .env file
     * @throws EnvException
     * @throws Exception
     */
    public function exec(): void
    {
        $environment = environment();

        if ($environment->hasKey('APP_KEY') && $environment->getValue('APP_KEY') !== '' && !$this->getOption('yes')) {
            if (!$this->confirm('The operation will remove the existing key and will create new one. Continue?')) {
                $this->info('Operation was canceled!');
                return;
            }
        }

        $environment
            ->setMutable(true)
            ->updateRow('APP_KEY', $this->generateRandomKey());

        $this->info('Application key successfully generated and stored.');
    }

    /**
     * Generates random string
     * @throws Exception
     */
    private function generateRandomKey(): string
    {
        return bin2hex(random_bytes(max(1, (int) $this->getOption('length'))));
    }
}
