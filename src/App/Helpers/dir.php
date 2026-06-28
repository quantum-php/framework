<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\App\App;

/**
 * Gets base directory
 */
function base_dir(): string
{
    return App::getBaseDir();
}

/**
 * Gets the log directory
 */
function logs_dir(): string
{
    return App::getBaseDir() . DS . 'logs';
}

/**
 * Gets the framework directory
 */
function framework_dir(): string
{
    return App::getBaseDir() . DS . 'vendor' . DS . 'quantum' . DS . 'framework' . DS . 'src';
}

/**
 * Gets modules directory
 */
function modules_dir(?string $moduleDir = null): string
{
    return $moduleDir ?? App::getBaseDir() . DS . 'modules';
}

/**
 * Gets public directory
 */
function public_dir(): string
{
    return App::getBaseDir() . DS . 'public';
}

/**
 * Gets uploads directory
 */
function uploads_dir(): string
{
    return public_dir() . DS . 'uploads';
}

/**
 * Gets assets directory
 */
function assets_dir(): string
{
    return public_dir() . DS . 'assets';
}

/**
 * Gets hooks directory
 */
function hooks_dir(): string
{
    return App::getBaseDir() . DS . 'hooks';
}

/**
 * Recursively deletes folder
 */
function deleteDirectoryWithFiles(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . DS . $file;
        is_dir($path) ? deleteDirectoryWithFiles($path) : unlink($path);
    }

    return rmdir($dir);
}

/**
 * Gets directory classes
 * @return array<string>
 */
function get_directory_classes(string $path): array
{
    $class_names = [];

    if (is_dir($path)) {
        $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $phpFiles = new RegexIterator($allFiles, '/\.php$/');

        foreach ($phpFiles as $file) {
            $class = pathinfo($file->getFilename());
            $class_names[] = $class['filename'];
        }
    }

    return $class_names;
}
