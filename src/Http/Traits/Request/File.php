<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Storage\Exceptions\FileUploadException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Storage\UploadedFile;

/**
 * Trait File
 * @package Quantum\Http
 */
trait File
{
    /**
     * Files
     * @var array<string, mixed>
     */
    private array $__files = [];

    /**
     * Checks to see if request contains file
     */
    public function hasFile(string $key): bool
    {
        if (!isset($this->__files[$key])) {
            return false;
        }

        if (!is_array($this->__files[$key]) && $this->__files[$key]->getErrorCode() != UPLOAD_ERR_OK) {
            return false;
        }

        if (is_array($this->__files[$key])) {
            foreach ($this->__files[$key] as $file) {
                if ($file->getErrorCode() != UPLOAD_ERR_OK) {
                    return false;
                }
            }

        }

        return true;
    }

    /**
     * Gets the file or array of file objects
     * @return mixed
     * @throws BaseException
     */
    public function getFile(string $key)
    {
        if (!$this->hasFile($key)) {
            throw FileUploadException::fileNotFound($key);
        }

        return $this->__files[$key];
    }

    /**
     * Handle files
     * @param array<string, mixed> $files
     * @return array<string, UploadedFile|array<UploadedFile>>
     */
    public function handleFiles(array $files): array
    {
        $formatted = [];

        foreach ($files as $key => $file) {
            if (!is_array($file) || !isset($file['name'])) {
                continue;
            }

            if (!is_array($file['name'])) {
                $formatted[$key] = new UploadedFile($file);
                continue;
            }

            if (!$this->isMultiFilePayload($file)) {
                continue;
            }

            $types = $file['type'];
            $tmpNames = $file['tmp_name'];
            $errors = $file['error'];
            $sizes = $file['size'];
            $multiFiles = [];

            foreach ($file['name'] as $index => $name) {
                if (!isset($types[$index], $tmpNames[$index], $errors[$index], $sizes[$index])) {
                    continue;
                }

                $multiFiles[$index] = new UploadedFile([
                    'name' => $name,
                    'type' => $types[$index],
                    'tmp_name' => $tmpNames[$index],
                    'error' => $errors[$index],
                    'size' => $sizes[$index],
                ]);
            }

            $formatted[$key] = $multiFiles;
        }

        return $formatted;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function isMultiFilePayload(array $file): bool
    {
        return isset($file['type'], $file['tmp_name'], $file['error'], $file['size'])
            && is_array($file['type'])
            && is_array($file['tmp_name'])
            && is_array($file['error'])
            && is_array($file['size']);
    }
}
