<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Uploads;

use Quantum\Storage\Exceptions\FileUploadException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Loader\Loader;
use Quantum\Loader\Setup;
use ReflectionException;
use Quantum\Di\Di;

class UploadConfigProvider
{
    /**
     * @return array<string, list<string>>
     * @throws FileUploadException|LoaderException|ConfigException|DiException|ReflectionException
     */
    public function getAllowedMimeTypesMap(): array
    {
        if (!config()->has('uploads')) {
            if (!Di::isRegistered(Loader::class)) {
                Di::register(Loader::class);
            }

            $loader = Di::get(Loader::class);
            $setup = new Setup('config', 'uploads');
            $loader->setup($setup);

            if (!$loader->fileExists()) {
                return [];
            }

            config()->import($setup);
        }

        $allowedMimeTypesMap = config()->get('uploads.allowed_mime_types') ?? [];

        if (!is_array($allowedMimeTypesMap)) {
            throw FileUploadException::incorrectMimeTypesConfig('uploads');
        }

        return $allowedMimeTypesMap;
    }
}
