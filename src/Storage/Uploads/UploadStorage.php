<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Uploads;

use Quantum\Storage\Contracts\LocalFilesystemAdapterInterface;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\UploadedFile;

class UploadStorage
{
    private LocalFilesystemAdapterInterface $localFileSystem;

    public function __construct(LocalFilesystemAdapterInterface $localFileSystem)
    {
        $this->localFileSystem = $localFileSystem;
    }

    public function store(UploadedFile $file, string $targetPath, ?FilesystemAdapterInterface $remoteFileSystem = null): bool
    {
        if ($remoteFileSystem) {
            return (bool) $remoteFileSystem->put($targetPath, $this->localFileSystem->get($file->getPathname()));
        }

        if ($file->isUploaded()) {
            return move_uploaded_file($file->getPathname(), $targetPath);
        }

        return $this->localFileSystem->copy($file->getPathname(), $targetPath);
    }
}
