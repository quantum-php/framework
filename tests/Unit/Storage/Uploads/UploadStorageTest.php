<?php

namespace Quantum\Tests\Unit\Storage\Uploads;

use Quantum\Storage\Adapters\Local\LocalFileSystemAdapter;
use Quantum\Storage\Contracts\FilesystemAdapterInterface;
use Quantum\Storage\Uploads\UploadStorage;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Storage\UploadedFile;
use Mockery;

class UploadStorageTest extends AppTestCase
{
    private string $sourceFile;
    private string $targetFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->sourceFile = base_dir() . DS . 'storage-source.tmp';
        $this->targetFile = base_dir() . DS . 'storage-target.tmp';

        file_put_contents($this->sourceFile, 'hello');
    }

    public function tearDown(): void
    {
        if (file_exists($this->sourceFile)) {
            unlink($this->sourceFile);
        }

        if (file_exists($this->targetFile)) {
            unlink($this->targetFile);
        }

        parent::tearDown();
    }

    public function testStoreCopiesFileWhenNotUploaded(): void
    {
        $storage = new UploadStorage(new LocalFileSystemAdapter());
        $uploadedFile = new UploadedFile([
            'name' => 'source.tmp',
            'tmp_name' => $this->sourceFile,
            'error' => 0,
            'size' => 5,
            'type' => 'text/plain',
        ]);

        $result = $storage->store($uploadedFile, $this->targetFile);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($this->targetFile));
    }

    public function testStoreUsesRemoteAdapterWhenProvided(): void
    {
        $storage = new UploadStorage(new LocalFileSystemAdapter());
        $uploadedFile = new UploadedFile([
            'name' => 'source.tmp',
            'tmp_name' => $this->sourceFile,
            'error' => 0,
            'size' => 5,
            'type' => 'text/plain',
        ]);

        $remote = Mockery::mock(FilesystemAdapterInterface::class);
        $remote->shouldReceive('put')
            ->once()
            ->with($this->targetFile, 'hello')
            ->andReturn(true);

        $result = $storage->store($uploadedFile, $this->targetFile, $remote);

        $this->assertTrue($result);
    }
}
