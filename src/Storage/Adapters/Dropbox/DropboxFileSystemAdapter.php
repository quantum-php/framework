<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Adapters\Dropbox;

use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Exception;

/**
 * Class DropboxFileSystemAdapter
 * @package Quantum\Storage
 */
class DropboxFileSystemAdapter implements FilesystemAdapterInterface
{
    private DropboxApp $dropboxApp;

    public function __construct(DropboxApp $dropboxApp)
    {
        $this->dropboxApp = $dropboxApp;
    }

    /**
     * @inheritDoc
     */
    public function makeDirectory(string $dirname, ?string $parentId = null): bool
    {
        try {
            $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_CREATE_FOLDER, $this->dropboxApp->path($dirname));
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function removeDirectory(string $dirname): bool
    {
        try {
            $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_DELETE_FILE, $this->dropboxApp->path($dirname));
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $filename): string|false
    {
        try {
            return (string) $this->dropboxApp->contentRequest(DropboxApp::ENDPOINT_DOWNLOAD_FILE, $this->dropboxApp->path($filename));
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function put(string $filename, $content, ?string $parentId = null)
    {
        try {
            $response = $this->dropboxApp->contentRequest(
                DropboxApp::ENDPOINT_UPLOAD_FILE,
                ['path' => '/' . $filename, 'mode' => 'overwrite'],
                $content
            );

            return $response->size;

        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, $content)
    {
        $fileContent = $this->get($filename);

        return $this->put($filename, $fileContent . $content);
    }

    /**
     * @inheritDoc
     */
    public function rename(string $oldName, string $newName): bool
    {
        try {
            $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_MOVE_FILE, [
                'from_path' => $this->dropboxApp->path($oldName),
                'to_path' => $this->dropboxApp->path($newName),
            ]);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $dest): bool
    {
        try {
            $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_COPY_FILE, [
                'from_path' => $this->dropboxApp->path($source),
                'to_path' => $this->dropboxApp->path($dest),
            ]);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function exists(string $filename): bool
    {
        return $this->isFile($filename);
    }

    /**
     * @inheritDoc
     */
    public function size(string $filename)
    {
        try {
            $meta = (array) $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_FILE_METADATA, $this->dropboxApp->path($filename));
            return $meta['size'];
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $filename): int|false
    {
        try {
            $meta = (array) $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_FILE_METADATA, $this->dropboxApp->path($filename));
            return isset($meta['server_modified']) ? strtotime($meta['server_modified']) : false;
        } catch (Exception) {
            return false;
        }

    }

    /**
     * @inheritDoc
     */
    public function remove(string $filename): bool
    {
        try {
            $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_DELETE_FILE, $this->dropboxApp->path($filename));
            return true;
        } catch (Exception) {
            return false;
        }

    }

    /**
     * @inheritDoc
     */
    public function isFile(string $filename): bool
    {
        try {
            $meta = (array) $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_FILE_METADATA, $this->dropboxApp->path($filename));
            return $meta['.tag'] == 'file';
        } catch (Exception) {
            return false;
        }

    }

    /**
     * @inheritDoc
     */
    public function isDirectory(string $dirname): bool
    {
        try {
            $meta = (array) $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_FILE_METADATA, $this->dropboxApp->path($dirname));
            return $meta['.tag'] == 'folder';
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function listDirectory(string $dirname)
    {
        try {
            $response = (array) $this->dropboxApp->rpcRequest(DropboxApp::ENDPOINT_LIST_FOLDER, $this->dropboxApp->path($dirname));
            return $response['entries'];
        } catch (Exception) {
            return false;
        }
    }
}
