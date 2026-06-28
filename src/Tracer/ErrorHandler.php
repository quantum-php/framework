<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Tracer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Quantum\Renderer\Exceptions\RendererException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Enums\StatusCode;
use Quantum\Logger\Logger;
use ReflectionException;
use ErrorException;
use Throwable;
use Exception;

/**
 * Class ErrorHandler
 * @package Quantum\Tracer
 */
class ErrorHandler
{
    private ?Logger $logger = null;
    private ?OutputInterface $cliOutput = null;
    private ExceptionSeverityResolver $severityResolver;
    private WebExceptionRenderer $webExceptionRenderer;

    public function __construct(
        ?ExceptionSeverityResolver $severityResolver = null,
        ?StackTraceFormatter $stackTraceFormatter = null,
        ?WebExceptionRenderer $webExceptionRenderer = null
    ) {
        $this->severityResolver = $severityResolver ?? new ExceptionSeverityResolver();
        $formatter = $stackTraceFormatter ?? new StackTraceFormatter();
        $this->webExceptionRenderer = $webExceptionRenderer ?? new WebExceptionRenderer($formatter);
    }

    private function __clone()
    {
        // Prevent cloning
    }

    public function setup(Logger $logger): void
    {
        $this->logger = $logger;

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    public function setCliOutput(OutputInterface $output): void
    {
        $this->cliOutput = $output;
    }

    /**
     * @throws ErrorException
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * @throws ConfigException|RendererException|DiException|BaseException|ReflectionException
     */
    public function handleException(Throwable $throwable): void
    {
        if (PHP_SAPI === 'cli') {
            $this->handleCliException($throwable);
        } else {
            $this->handleWebException($throwable);
        }
    }

    private function handleCliException(Throwable $throwable): void
    {
        $output = $this->cliOutput ?? new ConsoleOutput();

        if (!is_debug_mode()) {
            $output->writeln('<error>' . $throwable->getMessage() . '</error>');
            return;
        }

        $output->writeln('<error>' . $throwable::class . ': ' . $throwable->getMessage() . '</error>');
        $output->writeln('In ' . $throwable->getFile() . ':' . $throwable->getLine());
        $output->writeln($throwable->getTraceAsString());
    }

    /**
     * @throws DiException|BaseException|ReflectionException|Exception
     */
    private function handleWebException(Throwable $throwable): void
    {
        $errorType = $this->getErrorType($throwable);

        if (!is_debug_mode()) {
            $this->logError($throwable, $errorType);
        }

        try {
            $errorPage = $this->webExceptionRenderer->render($throwable, $errorType);
            response()->html($errorPage, StatusCode::INTERNAL_SERVER_ERROR);
            response()->send();
        } catch (Throwable) {
            response()->html('Internal Server Error', StatusCode::INTERNAL_SERVER_ERROR);
            response()->send();
        }
    }

    private function logError(Throwable $e, string $errorType): void
    {
        if ($this->logger === null) {
            return;
        }

        $logMethod = method_exists($this->logger, $errorType) ? $errorType : 'error';

        $this->logger->$logMethod($e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }

    /**
     * Gets the error type based on the exception class
     */
    private function getErrorType(Throwable $e): string
    {
        return $this->severityResolver->resolve($e);
    }
}
