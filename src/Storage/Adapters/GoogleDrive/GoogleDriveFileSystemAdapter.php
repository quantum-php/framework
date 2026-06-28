<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Adapters\GoogleDrive;

use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Exception;

/**
 * Class GoogleDriveFileSystemAdapter
 * @package Quantum\Storage
 */
class GoogleDriveFileSystemAdapter implements FilesystemAdapterInterface
{
    private GoogleDriveApp $googleDriveApp;

    public function __construct(GoogleDriveApp $googleDriveApp)
    {
        $this->googleDriveApp = $googleDriveApp;
    }

    /**
     * @inheritDoc
     */
    public function makeDirectory(string $dirname, ?string $parentId = null): bool
    {
        try {
            $data = [
                'name' => $dirname,
                'mimeType' => GoogleDriveApp::FOLDER_MIMETYPE,
                'parents' => $parentId ? [$parentId] : ['root'],
            ];

            $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL, $data);
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
        return $this->remove($dirname);
    }

    /**
     * @inheritDoc
     */
    public function get(string $filename): string|false
    {
        try {
            return (string) $this->googleDriveApp->getFileInfo($filename, true);
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
            if ($this->isFile($filename)) {
                $fileId = $filename;
            } else {
                $data = [
                    'name' => $filename,
                    'parents' => $parentId ? [$parentId] : ['root'],
                ];

                $newFile = $this->googleDriveApp->rpcRequest(
                    GoogleDriveApp::FILE_METADATA_URL,
                    $data
                );

                if (!isset($newFile->id)) {
                    throw new Exception('Google Drive file creation failed');
                }

                $fileId = $newFile->id;
            }

            return $this->googleDriveApp->rpcRequest(
                GoogleDriveApp::FILE_MEDIA_URL . '/' . $fileId . '?uploadType=media',
                $content,
                'PUT',
                'application/octet-stream'
            );

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
            $data = [
                'name' => $newName,
            ];

            $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $oldName, $data, 'PATCH');
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $dest = 'root'): bool
    {
        try {
            $data = [
                'parents' => [$dest],
            ];

            $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $source . '/copy', $data);
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
            $meta = (array) $this->googleDriveApp->getFileInfo($filename);
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
            $meta = (array) $this->googleDriveApp->getFileInfo($filename);
            return empty($meta['modifiedTime']) ? false : strtotime($meta['modifiedTime']);
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
            $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '/' . $filename, [], 'DELETE');
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
            $meta = (array) $this->googleDriveApp->getFileInfo($filename);

            return $meta['kind'] === GoogleDriveApp::DRIVE_FILE_KIND && $meta['mimeType'] != GoogleDriveApp::FOLDER_MIMETYPE;
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
            $meta = (array) $this->googleDriveApp->getFileInfo($dirname);

            return $meta['kind'] === GoogleDriveApp::DRIVE_FILE_KIND && $meta['mimeType'] === GoogleDriveApp::FOLDER_MIMETYPE;
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
            $params = [
                'q' => "'$dirname' in parents and trashed = false",
                'fields' => '*',
            ];
            $response = (array) $this->googleDriveApp->rpcRequest(GoogleDriveApp::FILE_METADATA_URL . '?' . http_build_query($params), [], 'GET');
            return $response['files'];
        } catch (Exception) {
            return false;
        }
    }
}
