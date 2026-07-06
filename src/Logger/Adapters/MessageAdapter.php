<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Logger\Adapters;

use Quantum\Logger\Contracts\ReportableInterface;
use Quantum\Debugger\Debugger;
use Stringable;

/**
 * Class MessageAdapter
 * @package Quantum\Logger
 */
class MessageAdapter implements ReportableInterface
{
    public function report(string $level, string|Stringable $message, ?array $context = []): void
    {
        $tab = $context['tab'] ?? Debugger::MESSAGES;

        $debugger = debugbar();

        if ($debugger->isEnabled()) {
            $debugger->addToStoreCell($tab, $level, $message);
        }
    }

}
