<?php

namespace Quantum\Tests\Unit\Storage\Adapters\Dropbox;

use Quantum\Storage\Adapters\Dropbox\DropboxFileSystemAdapter;
use Quantum\Storage\Adapters\Dropbox\DropboxApp;
use Quantum\Tests\Unit\AppTestCase;
use Mockery;

class DropboxFileSystemAdapterTest extends AppTestCase
{
    /**
     * @var DropboxFileSystemAdapter
     */
    protected $fs;

    private string $dirname = 'common';

    private string $filename = 'sample.txt';

    private string $content = 'This file was created via dropbox api';

    private static array $response = [];

    public function setUp(): void
    {
        parent::setUp();

        $dropboxAppMock = Mockery::mock(DropboxApp::class);

        $dropboxAppMock
            ->shouldReceive('rpcRequest')
            ->andReturnUsing(function ($endpoint, $params): array {
                self::$response = array_merge(self::$response, $params);
                return self::$response;
            });

        $dropboxAppMock
            ->shouldReceive('contentRequest')
            ->andReturnUsing(function ($endpoint, $params, $content = '') {
                if ($content) {
                    self::$response['content'] = $content;
                }

                return self::$response['content'] ?? false;
            });

        $dropboxAppMock
            ->shouldReceive('path')
            ->andReturnUsing(fn ($path): array => ['path' => '/' . trim($path, '/')]);

        $this->fs = new DropboxFileSystemAdapter($dropboxAppMock);
    }

    public function tearDown(): void
    {
        self::$response = [];

        parent::tearDown();
    }

    public function testDropboxMakeCheckRemoveDirectory(): void
    {
        $this->assertFalse($this->fs->isDirectory($this->dirname));

        self::$response['.tag'] = 'folder';

        $this->fs->makeDirectory($this->dirname);

        $this->assertTrue($this->fs->isDirectory($this->dirname));

        $this->fs->removeDirectory($this->dirname);

        self::$response = [];

        $this->assertFalse($this->fs->isDirectory($this->dirname));
    }

    public function testDropboxCreateGetCheckRemoveFile(): void
    {
        $this->assertFalse($this->fs->isFile($this->filename));

        self::$response['.tag'] = 'file';

        $this->fs->put($this->filename, $this->content);

        $this->assertTrue($this->fs->isFile($this->filename));

        $this->assertTrue($this->fs->exists($this->filename));

        $this->assertEquals($this->content, $this->fs->get($this->filename));

        $this->fs->remove($this->filename);

        self::$response = [];

        $this->assertFalse($this->fs->exists($this->filename));
    }

    public function testDropboxFileAppend(): void
    {
        self::$response['.tag'] = 'file';

        $this->fs->put($this->filename, $this->content);

        $this->assertTrue($this->fs->exists($this->filename));

        $moreContent = 'The sun is shining';

        $this->fs->append($this->filename, $moreContent);

        $this->assertEquals($this->content . $moreContent, $this->fs->get($this->filename));
    }

    public function testDropboxFileRename(): void
    {
        $this->fs->put($this->filename, $this->content);

        $newFilename = 'new_name.txt';

        $this->assertFalse($this->fs->exists($newFilename));

        self::$response['.tag'] = 'file';

        $this->fs->rename($this->filename, $newFilename);

        $this->assertTrue($this->fs->exists($newFilename));

        $this->fs->remove($newFilename);
    }

    public function testDropboxFileCopy(): void
    {
        $dirName = 'testing';

        $this->fs->makeDirectory($dirName);

        $this->fs->put($this->filename, $this->content);

        $this->assertFalse($this->fs->exists($dirName . '/' . $this->filename));

        self::$response['.tag'] = 'file';

        $this->fs->copy($this->filename, $dirName . '/' . $this->filename);

        $this->assertTrue($this->fs->exists($dirName . '/' . $this->filename));

        $this->fs->remove($dirName . '/' . $this->filename);

        $this->fs->removeDirectory($dirName);
    }

    public function testDropboxFileSize(): void
    {
        $text = 'some bytes';

        $this->fs->put($this->filename, $text);

        self::$response['size'] = strlen($text);

        $this->assertEquals(10, $this->fs->size($this->filename));
    }

    public function testDropboxFileLastModified(): void
    {
        $modified = '2023-02-12T15:50:38Z';

        $this->fs->put($this->filename, $this->content);

        self::$response['server_modified'] = $modified;

        $this->assertIsInt($this->fs->lastModified($this->filename));

        $this->assertEquals(strtotime($modified), $this->fs->lastModified($this->filename));
    }

    public function testDropboxListDirectory(): void
    {
        self::$response['entries'] = [
            [
                '.tag' => 'folder',
                'name' => 'empty',
                'path_lower' => '/test/empty',
                'path_display' => '/test/empty',
                'id' => 'id:SziOaBdnr3oAAAAAAAAAWQ',
            ],
            [
                '.tag' => 'file',
                'name' => 'logo.png',
                'path_lower' => '/test/logo.png',
                'path_display' => '/test/logo.png',
                'id' => 'id:SziOaBdnr3oAAAAAAAAAVQ',
                'client_modified' => '2023-02-24T15:34:43Z',
                'server_modified' => '2023-02-24T15:34:44Z',
                'rev' => '5f573de5b0b13c07503b1',
                'size' => 3455,
                'is_downloadable' => true,
                'content_hash' => '4be946abd21ac3fbe9e363192dbb7efe18a2071f5e287fef0f9580bf67141c55',
            ],
            [
                '.tag' => 'file',
                'name' => 'Image 19.jpg',
                'path_lower' => '/test/image 19.jpg',
                'path_display' => '/test/Image 19.jpg',
                'id' => 'id:SziOaBdnr3oAAAAAAAAAVw',
                'client_modified' => '2023-03-01T17:12:58Z',
                'server_modified' => '2023-03-01T17:12:58Z',
                'rev' => '5f5d9d2e5217cc07503b1',
                'size' => 2083432,
                'is_downloadable' => true,
                'content_hash' => '52e9a39cad88c0a5518e40d02c5e66f13634d102211923f3cbf919fe52c652b7',
            ],
        ];

        $entries = $this->fs->listDirectory('test');

        $this->assertIsArray($entries);

        $this->assertIsArray(current($entries));

        self::$response = [];

        $this->assertFalse($this->fs->listDirectory('test'));

    }
}
