<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

use Quantum\Storage\Exceptions\FileUploadException;
use Quantum\Storage\UploadedFile;

/**
 * Trait File
 * @package Quantum\Validation
 */
trait File
{
    /**
     * Validates file size
     */
    public function fileSize(UploadedFile $file, int $maxSize, ?int $minSize = null): bool
    {
        $size = $file->getSize();
        $minSize ??= 0;

        return $size >= $minSize && $size <= $maxSize;
    }

    /**
     * Validates file mime type
     */
    public function fileMimeType(UploadedFile $file, string ...$mimeTypes): bool
    {
        return in_array($file->getMimetype(), $mimeTypes);
    }

    /**
     * Validates file extension
     */
    public function fileExtension(UploadedFile $file, string ...$extensions): bool
    {
        return in_array($file->getExtension(), $extensions);
    }

    /**
     * Validates image dimensions
     * @throws FileUploadException
     */
    public function imageDimensions(UploadedFile $file, ?int $width = null, ?int $height = null): bool
    {
        $dimensions = $file->getDimensions();

        if ($dimensions === []) {
            return true;
        }

        if ($width !== null && $dimensions['width'] != $width) {
            return false;
        }

        if ($height !== null && $dimensions['height'] != $height) {
            return false;
        }

        return true;
    }
}
