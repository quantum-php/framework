<?php

namespace Quantum\Tests\Unit\Storage\Adapters\Local;

use Quantum\Storage\Adapters\Local\LocalFileSystemAdapter;
use Quantum\Tests\Unit\AppTestCase;

class LocalFileSystemAdapterTest extends AppTestCase
{
    /**
     * @var LocalFileSystemAdapter
     */
    protected $fs;

    private string $dirname;

    private string $filename;

    private string $content = 'Hello world';

    public function setUp(): void
    {
        parent::setUp();

        $this->fs = new LocalFileSystemAdapter();

        $this->dirname = base_dir() . DS . 'common';

        $this->filename = base_dir() . DS . 'test.txt';

        $GLOBALS['include_count'] = 0;
    }

    public function tearDown(): void
    {
        if ($this->fs->exists($this->filename)) {
            $this->fs->remove($this->filename);
        }

        parent::tearDown();
    }

    public function testLocalMakeRemoveDirectory(): void
    {
        $this->assertDirectoryDoesNotExist($this->dirname);

        $this->fs->makeDirectory($this->dirname);

        $this->assertDirectoryExists($this->dirname);

        $this->fs->removeDirectory($this->dirname);

        $this->assertDirectoryDoesNotExist($this->dirname);
    }

    public function testLocalFileGetPut(): void
    {
        $this->fs->put($this->filename, $this->content);

        $this->assertFileExists($this->filename);

        $this->assertEquals($this->content, $this->fs->get($this->filename));
    }

    public function testLocalFileAppend(): void
    {
        $this->fs->put($this->filename, $this->content);

        $this->assertFileExists($this->filename);

        $moreContent = 'The sun is shining';

        $this->fs->append($this->filename, $moreContent);

        $this->assertEquals($this->content . $moreContent, $this->fs->get($this->filename));
    }

    public function tesLocalFileRename(): void
    {
        $this->fs->put($this->filename, $this->content);

        $newFilename = base_dir() . DS . 'new_name.txt';

        $this->assertFileDoesNotExist($newFilename);

        $this->fs->rename($this->filename, $newFilename);

        $this->assertFileExists($newFilename);

        $this->fs->remove($newFilename);
    }

    public function testLocalFileCopy(): void
    {
        $this->fs->makeDirectory($this->dirname);

        $this->fs->put($this->filename, $this->content);

        $this->assertFileDoesNotExist($this->dirname . DS . $this->filename);

        $this->fs->copy($this->filename, $this->dirname . DS . 'test.txt');

        $this->assertFileExists($this->dirname . DS . 'test.txt');

        $this->fs->remove($this->dirname . DS . 'test.txt');

        $this->fs->removeDirectory($this->dirname);
    }

    public function testLocalFileExists(): void
    {
        $newFilename = base_dir() . DS . 'new_name.txt';

        $this->assertFalse($this->fs->exists($newFilename));

        $this->fs->put($newFilename, $this->content);

        $this->assertTrue($this->fs->exists($newFilename));

        $this->fs->remove($newFilename);
    }

    public function testLocalFileSize(): void
    {
        $this->fs->put($this->filename, 'some bytes');

        $this->assertEquals(10, $this->fs->size($this->filename));
    }

    public function testLocalFileLastModified(): void
    {
        $this->fs->put($this->filename, $this->content);

        $this->assertIsInt($this->fs->lastModified($this->filename));
    }

    public function testLocalFileRemove(): void
    {
        $this->fs->put($this->filename, $this->content);

        $this->assertFileExists($this->filename);

        $this->fs->remove($this->filename);

        $this->assertFileDoesNotExist($this->filename);
    }

    public function testLocalIsFile(): void
    {
        $this->assertTrue($this->fs->isFile(__FILE__));

        $this->assertFalse($this->fs->isFile(__DIR__));
    }

    public function testLocalIsDirectory(): void
    {
        $this->assertTrue($this->fs->isDirectory(__DIR__));

        $this->assertFalse($this->fs->isDirectory(__FILE__));
    }

    public function testLocalListDirectory(): void
    {
        $entries = $this->fs->listDirectory(base_dir());

        $this->assertIsArray($entries);

        $this->assertIsString(current($entries));

        $this->assertEmpty($this->fs->listDirectory('nowhere'));
    }

    public function testLocalGlob(): void
    {
        $entries = $this->fs->glob(base_dir() . DS . '*.log');

        $this->assertIsArray($entries);

        $this->assertIsString(current($entries));
    }

    public function testLocalFileIsReadableWritable(): void
    {
        $this->fs->put($this->filename, '');

        $this->assertTrue($this->fs->isReadable($this->filename));

        $this->assertTrue($this->fs->isWritable($this->filename));

        $this->fs->remove($this->filename);

        $this->assertFalse($this->fs->isReadable($this->filename));

        $this->assertFalse($this->fs->isWritable($this->filename));
    }

    public function testLocalFileGetLines(): void
    {
        $lineOne = 'It\'s was started then ' . PHP_EOL;
        $lineTwo = 'something happens' . PHP_EOL;
        $lineThree = 'between lines' . PHP_EOL;
        $lineFour = 'and it\'s gone' . PHP_EOL;

        $this->fs->put($this->filename, $lineOne . $lineTwo . $lineThree . $lineFour);

        $lines = $this->fs->getLines($this->filename, 1, 2);

        $this->assertIsArray($lines);

        $this->assertCount(2, $lines);

        $this->assertEquals(trim($lineTwo, PHP_EOL), $lines[1]);

        $this->assertEquals(trim($lineThree, PHP_EOL), $lines[2]);

        $lines = $this->fs->getLines($this->filename);

        $this->assertIsArray($lines);

        $this->assertCount(4, $lines);
    }

    public function testLocalFileNameAndExtension(): void
    {
        $this->assertEquals('test', $this->fs->fileName($this->filename));

        $this->assertEquals('txt', $this->fs->extension($this->filename));
    }

    public function testLocalFileNameWithExtension(): void
    {
        $this->assertEquals('test.txt', $this->fs->fileNameWithExtension($this->filename));
    }

    public function testLocalRequireOutput(): void
    {
        $this->fs->put($this->filename, $this->content);

        ob_start();

        $this->fs->require($this->filename);

        $output = ob_get_clean();

        $this->assertSame($this->content, $output);
    }

    public function testLocalRequireReturn(): void
    {
        $this->fs->put($this->filename, "<?php return ['key' => 'abc'];");

        $result = $this->fs->require($this->filename);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('key', $result);

        $this->assertEquals($result['key'], 'abc');
    }

    public function testLocalRequireBehavior(): void
    {
        $this->fs->put($this->filename, '<?php return $GLOBALS["include_count"]++;');

        $this->fs->require($this->filename);
        $this->fs->require($this->filename);
        $this->fs->require($this->filename);

        $this->assertEquals(3, $GLOBALS['include_count']);
    }

    public function testLocalRequireOnceBehavior(): void
    {
        $file = base_dir() . DS . 'track1.txt';

        $this->fs->put($file, '<?php return $GLOBALS["include_count"]++;');

        $this->fs->require($file, true);
        $this->fs->require($file, true);
        $this->fs->require($file, true);

        $this->assertEquals(1, $GLOBALS['include_count']);

        $this->fs->remove($file);
    }

    public function testLocalIncludeOutput(): void
    {
        $this->fs->put($this->filename, $this->content);

        ob_start();

        $this->fs->include($this->filename);

        $output = ob_get_clean();

        $this->assertSame($this->content, $output);
    }

    public function testLocalIncludeReturn(): void
    {
        $this->fs->put($this->filename, "<?php return ['key' => 'abc'];");

        $result = $this->fs->include($this->filename);

        $this->assertIsArray($result);

        $this->assertArrayHasKey('key', $result);

        $this->assertEquals($result['key'], 'abc');
    }

    public function testLocalIncludeBehavior(): void
    {
        $this->fs->put($this->filename, '<?php return $GLOBALS["include_count"]++;');

        $this->fs->include($this->filename);
        $this->fs->include($this->filename);
        $this->fs->include($this->filename);

        $this->assertEquals(3, $GLOBALS['include_count']);
    }

    public function testLocalIncludeOnceBehavior(): void
    {
        $file = base_dir() . DS . 'track2.txt';

        $this->fs->put($file, '<?php return $GLOBALS["include_count"]++;');

        $this->fs->include($file, true);
        $this->fs->include($file, true);
        $this->fs->include($file, true);

        $this->assertEquals(1, $GLOBALS['include_count']);

        $this->fs->remove($file);
    }
}
