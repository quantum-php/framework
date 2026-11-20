<?php

namespace Quantum\Tests\Unit\Archive\Adapters;

use Quantum\Archive\Adapters\ZipAdapter;
use Quantum\Tests\Unit\AppTestCase;

class ZipAdapterTest extends AppTestCase
{
    private ZipAdapter $zipArchive;
    private string $archiveName;

    public function setUp(): void
    {
        parent::setUp();

        $this->archiveName = base_dir() . DS . 'test.zip';
        $this->zipArchive = new ZipAdapter();
        $this->zipArchive->setName($this->archiveName);
    }

    public function tearDown(): void
    {
        unset($this->zipArchive);

        if ($this->fs->exists($this->archiveName)) {
            $this->fs->remove($this->archiveName);
        }

        parent::tearDown();
    }

    public function testAddEmptyDirToZip(): void
    {
        $this->assertFalse($this->zipArchive->offsetExists('directoryOne'));

        $this->assertTrue($this->zipArchive->addEmptyDir('directoryOne'));

        $this->assertTrue($this->zipArchive->offsetExists('directoryOne'));
    }

    public function testAddFileToZip(): void
    {
        $this->assertFalse($this->zipArchive->offsetExists('app.conf'));

        $this->assertTrue(
            $this->zipArchive->addFile(
                base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
                'app.conf'
            )
        );

        $this->assertTrue($this->zipArchive->offsetExists('app.conf'));
    }

    public function testAddFromStringToZip(): void
    {
        $this->assertTrue($this->zipArchive->addFromString('custom.text', 'Just a sample text'));

        $this->assertTrue($this->zipArchive->offsetExists('custom.text'));
    }

    public function testAddMultipleFilesToZip(): void
    {
        $this->assertTrue($this->zipArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]));

        $this->assertTrue($this->zipArchive->offsetExists('app.conf'));

        $this->assertTrue($this->zipArchive->offsetExists('session.conf'));
    }

    public function testZipCount(): void
    {
        $this->assertTrue($this->zipArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]));

        $this->assertEquals(2, $this->zipArchive->count());
    }

    public function testExtractToFromZip(): void
    {
        $content = 'Just a sample text';

        $this->zipArchive->addFromString('sample.txt', $content);

        $this->assertTrue($this->zipArchive->extractTo(base_dir()));

        $this->assertFileExists(base_dir() . DS . 'sample.txt');

        $this->assertEquals($content, $this->fs->get(base_dir() . DS . 'sample.txt'));

        $this->fs->remove(base_dir() . DS . 'sample.txt');
    }

    public function testDeleteFileFromZip(): void
    {
        $this->zipArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]);

        $this->assertEquals(2, $this->zipArchive->count());

        $this->zipArchive->addFromString('sample.txt', 'Just a sample text');

        $this->assertEquals(3, $this->zipArchive->count());

        $this->zipArchive->deleteFile('sample.txt');

        $this->assertFalse($this->zipArchive->offsetExists('sample.txt'));

        $this->assertEquals(2, $this->zipArchive->count());
    }

    public function testDeleteMultipleFilesFromZip(): void
    {
        $this->zipArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'directoryOne/session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]);

        $this->assertEquals(2, $this->zipArchive->count());

        $this->assertTrue($this->zipArchive->offsetExists('app.conf'));
        $this->assertTrue($this->zipArchive->offsetExists('directoryOne/session.conf'));

        $this->assertTrue($this->zipArchive->deleteMultipleFiles([
            'directoryOne/session.conf',
            'app.conf',
        ]));

        $this->assertEquals(0, $this->zipArchive->count());

        $this->assertFalse($this->zipArchive->offsetExists('directoryOne/session.conf'));

        $this->assertFalse($this->zipArchive->offsetExists('app.conf'));
    }
}
