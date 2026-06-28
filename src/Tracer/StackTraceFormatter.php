<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Tracer;

use Quantum\Storage\Contracts\LocalFilesystemAdapterInterface;
use Throwable;

final class StackTraceFormatter
{
    private const NUM_LINES = 10;

    /**
     * @return array<int, array{file: string, code: string}>
     */
    public function compose(Throwable $throwable): array
    {
        $trace[] = [
            'file' => $throwable->getFile(),
            'code' => $this->getSourceCode($throwable->getFile(), $throwable->getLine(), 'error-line'),
        ];

        foreach ($throwable->getTrace() as $item) {
            if (($item['class'] ?? null) === ErrorHandler::class) {
                continue;
            }

            if (isset($item['file'])) {
                $trace[] = [
                    'file' => $item['file'],
                    'code' => $this->getSourceCode($item['file'], $item['line'] ?? 1, 'switch-line'),
                ];
            }
        }

        return $trace;
    }

    public function getSourceCode(string $filename, int $lineNumber, string $className): string
    {
        $lineNumber--;

        $halfLines = intdiv(self::NUM_LINES, 2);
        $start = max($lineNumber - $halfLines, 1);

        $adapter = fs()->getAdapter();

        if (!$adapter instanceof LocalFilesystemAdapterInterface) {
            return '';
        }

        $lines = $adapter->getLines($filename, $start, self::NUM_LINES);

        $code = '<ol start="' . key($lines) . '">';

        foreach ($lines as $currentLineNumber => $line) {
            $code .= $this->formatLineItem($currentLineNumber, $line, $lineNumber, $className);
        }

        return $code . '</ol>';
    }

    public function formatLineItem(int $currentLineNumber, string $line, int $lineNumber, string $className): string
    {
        $highlightClass = $currentLineNumber === $lineNumber ? " class=\"{$className}\"" : '';
        $encodedLine = htmlspecialchars($line, ENT_QUOTES);

        return sprintf('<li%s><pre>%s</pre></li>', $highlightClass, $encodedLine);
    }
}
