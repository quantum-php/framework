<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Uploads;

/**
 * Holds upload MIME policy rules and validates extension/MIME pairs.
 */
class UploadPolicy
{
    /**
     * @var array<string, list<string>>
     */
    private array $allowedMimeTypes;

    /**
     * @param array<string, list<string>|string> $allowedMimeTypes
     */
    public function __construct(array $allowedMimeTypes)
    {
        $this->allowedMimeTypes = $this->normalize($allowedMimeTypes);
    }

    /**
     * @param array<string, list<string>|string> $allowedMimeTypes
     */
    public function merge(array $allowedMimeTypes): void
    {
        $this->allowedMimeTypes = array_merge_recursive($this->allowedMimeTypes, $this->normalize($allowedMimeTypes));
    }

    /**
     * @param array<string, list<string>|string> $allowedMimeTypes
     */
    public function replace(array $allowedMimeTypes): void
    {
        $this->allowedMimeTypes = $this->normalize($allowedMimeTypes);
    }

    public function isAllowed(string $extension, string $mimeType): bool
    {
        $extension = strtolower($extension);
        $mimeType = strtolower($mimeType);

        return isset($this->allowedMimeTypes[$mimeType])
            && in_array($extension, $this->allowedMimeTypes[$mimeType], true);
    }

    /**
     * @param array<string, list<string>|string> $allowedMimeTypes
     * @return array<string, list<string>>
     */
    private function normalize(array $allowedMimeTypes): array
    {
        $normalized = [];

        foreach ($allowedMimeTypes as $mimeType => $extensions) {
            $values = is_array($extensions) ? $extensions : [$extensions];
            $normalized[$mimeType] = array_values(array_map('strval', $values));
        }

        return $normalized;
    }
}
