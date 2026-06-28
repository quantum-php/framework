<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Compose a message
 * @param string|array<string> $params
 */
function _message(string $subject, $params): string
{
    if (is_array($params)) {
        return preg_replace_callback('/{%\d+}/', function (array $matches) use (&$params): string {
            return array_shift($params) ?? '';
        }, $subject) ?? $subject;
    } else {
        return preg_replace('/{%\d+}/', $params, $subject) ?? $subject;
    }
}

/**
 * Validates base64 string
 */
function valid_base64(string $string): bool
{
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
        return false;
    }

    $decoded = base64_decode($string, true);

    return $decoded !== false && base64_encode($decoded) === $string;
}

/**
 * Generates random number sequence
 */
function random_number(int $length = 10): int
{
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= random_int(0, 9);
    }
    return (int) $randomString;
}

/**
 * Slugify the string
 */
function slugify(string $text): string
{
    $text = trim($text, ' ');
    $text = preg_replace('/[^\p{L}\p{N}]/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/', '-', $text) ?? $text;
    $text = trim($text, '-');
    $text = mb_strtolower($text);

    if ($text === '' || $text === '0') {
        return 'n-a';
    }

    return $text;
}

/**
 * Checks the app debug mode
 */
function is_debug_mode(): bool
{
    return filter_var(config()->get('app.debug'), FILTER_VALIDATE_BOOLEAN);
}

/**
 * Exports the variable
 * @param mixed $var
 * @throws ExceptionInterface
 */
function export($var): string
{
    return VarExporter::export($var);
}
