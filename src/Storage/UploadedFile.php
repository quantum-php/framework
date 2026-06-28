<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage;

use Quantum\Storage\Contracts\LocalFilesystemAdapterInterface;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Storage\Exceptions\FileUploadException;
use Quantum\Storage\Uploads\UploadConfigProvider;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Storage\Uploads\UploadStorage;
use Quantum\App\Exceptions\BaseException;
use Quantum\Storage\Uploads\UploadPolicy;
use Quantum\Di\Exceptions\DiException;
use Gumlet\ImageResizeException;
use ReflectionException;
use Gumlet\ImageResize;
use RuntimeException;
use SplFileInfo;
use finfo;

/**
 * Class File
 * @package Quantum\Storage
 */
class UploadedFile extends SplFileInfo
{
    /**
     * Local File System
     */
    protected ?LocalFilesystemAdapterInterface $localFileSystem = null;

    /**
     * Remove File System
     */
    protected ?FilesystemAdapterInterface $remoteFileSystem = null;

    /**
     * Original file name provided by client
     */
    protected ?string $originalName = null;

    /**
     * File name (without extension)
     */
    protected ?string $name = null;

    /**
     * File extension
     */
    protected ?string $extension = null;

    /**
     * File mime type
     */
    protected ?string $mimetype = null;

    /**
     * ImageResize function name
     */
    protected ?string $imageModifierFuncName = null;

    /**
     * ImageResize function arguments
     */
    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * Upload error code messages
     * @var array<int, string>
     */
    protected array $errorMessages = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload',
    ];

    /**
     * Allowed mime types => allowed extensions map
     * @var array<string, list<string>>
     */
    protected array $allowedMimeTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'application/pdf' => ['pdf'],
    ];

    /**
     * Upload error code
     */
    protected int $errorCode;

    /**
     * Whether mime types were loaded from config
     */
    protected bool $mimeTypesLoaded = false;

    /**
     * Whether mime types were explicitly set by caller
     */
    protected bool $mimeTypesOverridden = false;

    private ?UploadPolicy $uploadPolicy = null;

    private ?UploadStorage $uploadStorage = null;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(array $meta)
    {
        $this->originalName = $meta['name'];
        $this->errorCode = $meta['error'];

        parent::__construct($meta['tmp_name']);
    }

    /**
     * Sets the allowed mime types => extensions map
     * @param array<string, string> $allowedMimeTypes
     */
    public function setAllowedMimeTypes(array $allowedMimeTypes, bool $merge = true): UploadedFile
    {
        $policy = $this->getUploadPolicy();
        $merge ? $policy->merge($allowedMimeTypes) : $policy->replace($allowedMimeTypes);
        $this->mimeTypesOverridden = true;
        $this->mimeTypesLoaded = true;
        return $this;
    }

    /**
     * Get name
     */
    public function getName(): string
    {
        if (!$this->name) {
            $this->name = $this->getLocalFileSystem()->fileName($this->originalName ?? '');
        }

        return $this->name;
    }

    /**
     * Set name (without extension)
     */
    public function setName(string $name): UploadedFile
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the remote file system adapter
     */
    public function setRemoteFileSystem(FilesystemAdapterInterface $remoteFileSystem): UploadedFile
    {
        $this->remoteFileSystem = $remoteFileSystem;
        return $this;
    }

    /**
     * Gets the remote file system adapter
     */
    public function getRemoteFileSystem(): ?FilesystemAdapterInterface
    {
        return $this->remoteFileSystem;
    }

    /**
     * Get file extension (without leading dot)
     */
    public function getExtension(): string
    {
        if (!$this->extension) {
            $this->extension = strtolower($this->getLocalFileSystem()->extension($this->originalName ?? ''));
        }

        return $this->extension;
    }

    /**
     * Get file name with extension
     */
    public function getNameWithExtension(): string
    {
        return $this->getName() . '.' . $this->getExtension();
    }

    /**
     * Get mime type
     */
    public function getMimeType(): string
    {
        if (!$this->mimetype) {
            $fileInfo = new finfo(FILEINFO_MIME);
            $mimetype = $fileInfo->file($this->getPathname());

            if (!is_string($mimetype)) {
                throw new RuntimeException('Failed to determine MIME type for: ' . $this->getPathname());
            }

            $mimetypeParts = preg_split('/\s*[;,]\s*/', $mimetype);

            if (!is_array($mimetypeParts) || empty($mimetypeParts[0])) {
                throw new RuntimeException('Failed to parse MIME type: ' . $mimetype);
            }

            $this->mimetype = strtolower($mimetypeParts[0]);
            unset($fileInfo);
        }

        return $this->mimetype;
    }

    /**
     * Get md5
     */
    public function getMd5(): string
    {
        return md5_file($this->getPathname()) ?: '';
    }

    /**
     * Get image dimensions
     * @return array{width: int<0, max>, height: int<0, max>}
     * @throws FileUploadException
     */
    public function getDimensions(): array
    {
        if (!$this->isImage($this->getPathname())) {
            throw FileUploadException::fileTypeNotAllowed($this->getExtension());
        }

        $size = getimagesize($this->getPathname());

        if ($size === false) {
            throw FileUploadException::fileTypeNotAllowed($this->getExtension());
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    /**
     * Save the uploaded file
     * @param string $dest
     * @param bool $overwrite
     * @return bool
     * @throws FileUploadException|FileSystemException|ImageResizeException|BaseException|ReflectionException
     */
    public function save(string $dest, bool $overwrite = false): bool
    {
        $this->ensureAllowedMimeTypesLoaded();
        $localFileSystem = $this->getLocalFileSystem();

        if ($this->errorCode !== UPLOAD_ERR_OK) {
            throw new FileUploadException($this->getErrorMessage());
        }

        if (!$localFileSystem->isFile($this->getPathname())) {
            throw FileUploadException::fileNotFound($this->getPathname());
        }

        if (!$this->getUploadPolicy()->isAllowed($this->getExtension(), $this->getMimeType())) {
            throw FileUploadException::fileTypeNotAllowed($this->getExtension());
        }

        $filePath = $dest . DS . $this->getNameWithExtension();

        if (!$this->remoteFileSystem) {
            if (!$localFileSystem->isDirectory($dest)) {
                throw FileSystemException::directoryNotExists($dest);
            }

            if (!$localFileSystem->isWritable($dest)) {
                throw FileSystemException::directoryNotWritable($dest);
            }

            if ($overwrite === false && $localFileSystem->exists($filePath)) {
                throw FileSystemException::fileAlreadyExists($filePath);
            }
        }

        if (!$this->getUploadStorage()->store($this, $filePath, $this->remoteFileSystem)) {
            return false;
        }

        if ($this->imageModifierFuncName) {
            $this->applyModifications($filePath);
        }

        return true;
    }

    /**
     * Sets modification function on image
     * @param array<mixed> $params
     * @throws FileUploadException|LangException|BaseException
     */
    public function modify(string $funcName, array $params): UploadedFile
    {
        if (!$this->isImage($this->getPathname())) {
            throw FileUploadException::fileTypeNotAllowed($this->getExtension());
        }

        if (!method_exists(ImageResize::class, $funcName)) {
            throw BaseException::methodNotSupported($funcName, ImageResize::class);
        }

        $this->imageModifierFuncName = $funcName;
        $this->params = $params;

        return $this;
    }

    /**
     * Gets the error code
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Gets the error message from code
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessages[$this->errorCode];
    }

    /**
     * Tells whether the file was uploaded
     */
    public function isUploaded(): bool
    {
        return is_uploaded_file($this->getPathname());
    }

    /**
     * Checks if the given file is image
     */
    public function isImage(string $filePath): bool
    {
        return (bool) getimagesize($filePath);
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function getLocalFileSystem(): LocalFilesystemAdapterInterface
    {
        if ($this->localFileSystem) {
            return $this->localFileSystem;
        }

        $adapter = fs()->getAdapter();

        if (!$adapter instanceof LocalFilesystemAdapterInterface) {
            throw FileSystemException::notInstanceOf(
                $adapter::class,
                LocalFilesystemAdapterInterface::class
            );
        }

        $this->localFileSystem = $adapter;

        return $this->localFileSystem;
    }

    /**
     * @throws FileUploadException|LoaderException|ConfigException|DiException|ReflectionException
     */
    private function ensureAllowedMimeTypesLoaded(): void
    {
        if ($this->mimeTypesLoaded || $this->mimeTypesOverridden) {
            return;
        }

        $this->getUploadPolicy()->merge(
            (new UploadConfigProvider())->getAllowedMimeTypesMap()
        );
        $this->mimeTypesLoaded = true;
    }

    /**
     * Applies modifications on image
     * @param string $filePath
     * @return void
     * @throws ImageResizeException
     */
    protected function applyModifications(string $filePath)
    {
        $image = new ImageResize($filePath);
        $callable = [$image, $this->imageModifierFuncName ?? ''];

        if (!is_callable($callable)) {
            throw new RuntimeException('Invalid image modifier: ' . ($this->imageModifierFuncName ?? 'null'));
        }

        call_user_func_array($callable, array_values($this->params));

        $image->save($filePath);
    }

    private function getUploadPolicy(): UploadPolicy
    {
        if (!$this->uploadPolicy) {
            $this->uploadPolicy = new UploadPolicy($this->allowedMimeTypes);
        }

        return $this->uploadPolicy;
    }

    /**
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    private function getUploadStorage(): UploadStorage
    {
        if (!$this->uploadStorage) {
            $this->uploadStorage = new UploadStorage($this->getLocalFileSystem());
        }

        return $this->uploadStorage;
    }
}
