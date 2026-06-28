<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Console\Commands;

use Quantum\Console\CliCommand;
use RuntimeException;
use Throwable;

/**
 * Class ServeCommand
 * @package Quantum\Console
 */
class ServeCommand extends CliCommand
{
    /**
     * Platform Windows
     */
    public const PLATFORM_WINDOWS = 'Windows';

    /**
     * Platform Linux
     */
    public const PLATFORM_LINUX = 'Linux';

    /**
     * Platform Mac
     */
    public const PLATFORM_MAC = 'Darwin';

    /**
     * Maximum valid TCP/UDP port number
     */
    private const MAX_PORT = 65535;

    /**
     * The console command name.
     */
    protected ?string $name = 'serve';

    /**
     * The console command description.
     */
    protected ?string $description = 'Serves the application on the PHP development server';

    /**
     * Default host
     */
    protected string $defaultHost = '127.0.0.1';

    /**
     * Default port
     */
    protected int $defaultPort = 8000;

    /**
     * Max ports to scan
     */
    protected int $maxPortScan = 50;

    /**
     * Command arguments
     * @var array<int, array<int|string, mixed>>
     */
    protected array $options = [
        ['host', null, 'optional', 'Host', '127.0.0.1'],
        ['port', null, 'optional', 'Port', '8000'],
        ['open', 'o', 'none', 'Open browser'],
    ];

    /**
     * Execute the command.
     */
    public function exec(): void
    {
        $host = $this->host();
        $startPort = $this->port();

        $serverProcess = $this->startServerOnAvailablePort($host, $startPort);

        $this->handleServerExecution($serverProcess);
    }

    /**
     * Start the server on the first available port.
     * @throws RuntimeException
     * @return array<string, mixed>
     */
    protected function startServerOnAvailablePort(string $host, int $startPort): array
    {
        for ($i = 0; $i < $this->maxPortScan && $startPort + $i <= 65535; $i++) {
            $port = $startPort + $i;

            if ($this->isPortInUse($host, $port)) {
                continue;
            }

            $url = "http://{$host}:{$port}";
            $this->info("Starting development server at: {$url}");

            $process = $this->startPhpServer($host, $port);

            try {
                $this->waitUntilServerIsReady($host, $port, $process);

                return [
                    'process' => $process,
                    'port' => $port,
                    'url' => $url,
                ];
            } catch (Throwable) {
                $this->cleanupProcess($process);
            }
        }

        throw new RuntimeException('Unable to start PHP server on any available port.');
    }

    /**
     * Handle server execution (browser opening and process monitoring).
     * @param array<string, mixed> $serverData
     */
    protected function handleServerExecution(array $serverData): void
    {
        if ($this->shouldOpenBrowser()) {
            $this->openBrowser($serverData['url']);
        }

        $this->waitForProcess($serverData['process']);
    }

    /**
     * Clean up process resource.
     * @param resource $process
     */
    protected function cleanupProcess($process): void
    {
        if (is_resource($process)) {
            proc_close($process);
        }
    }

    /**
     * Check if port is already in use by another process.
     */
    protected function isPortInUse(string $host, int $port): bool
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.1);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * Start PHP built-in server.
     * @return resource
     */
    protected function startPhpServer(string $host, int $port)
    {
        $cmd = [
            PHP_BINARY,
            '-S',
            "{$host}:{$port}",
            '-t',
            'public',
        ];

        $descriptors = $this->serverProcessDescriptors();

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start PHP development server.');
        }

        return $process;
    }

    /**
     * Descriptors used to start the PHP built-in server process.
     * @return array<int, resource|array<int, string>|mixed>
     */
    protected function serverProcessDescriptors(): array
    {
        return [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ];
    }

    /**
     * Wait until the PHP server is ready to accept connections.
     * @param resource $process
     */
    protected function waitUntilServerIsReady(string $host, int $port, $process): void
    {
        $start = time();

        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                throw new RuntimeException('PHP server process died unexpectedly.');
            }

            $fp = @fsockopen($host, $port, $e, $s, 0.5);
            if ($fp) {
                fclose($fp);
                return;
            }

            if (time() - $start > 10) {
                throw new RuntimeException('Server failed to start within 10 seconds.');
            }

            usleep(200_000);
        }
    }

    /**
     * Block until the PHP server process exits.
     * @param resource $process
     */
    protected function waitForProcess($process): void
    {
        while (($status = proc_get_status($process))['running']) {
            usleep(200_000);
        }

        proc_close($process);
    }

    /**
     * Determine whether the browser should be opened.
     */
    protected function shouldOpenBrowser(): bool
    {
        return (bool) $this->getOption('open');
    }

    /**
     * Open the default browser.
     */
    protected function openBrowser(string $url): void
    {
        $cmd = $this->browserCommand($url);
        if (!$cmd) {
            return;
        }

        $descriptors = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ];

        $proc = proc_open($cmd[0] . ' ' . $cmd[1], $descriptors, $pipes);
        if (is_resource($proc)) {
            proc_close($proc);
        }
    }

    /**
     * Resolve platform-specific browser command.
     * @return array<int, string>|null
     */
    protected function browserCommand(string $url): ?array
    {
        return match (PHP_OS_FAMILY) {
            self::PLATFORM_WINDOWS => ['explorer.exe', $url],
            self::PLATFORM_LINUX => ['xdg-open', $url],
            self::PLATFORM_MAC => ['open', $url],
            default => null,
        };
    }

    /**
     * Get host option.
     */
    protected function host(): string
    {
        return (string) ($this->getOption('host') ?: $this->defaultHost);
    }

    /**
     * Get and validate port option.
     */
    protected function port(): int
    {
        $port = (int) ($this->getOption('port') ?: $this->defaultPort);

        if ($port < 1 || $port > self::MAX_PORT) {
            throw new RuntimeException('Port must be between 1 and ' . self::MAX_PORT . ", got: {$port}");
        }

        return $port;
    }
}
