<?php

namespace Quantum\Tests\Unit\Archive\Adapters;

use Quantum\Archive\Adapters\PharAdapter;
use Quantum\Tests\Unit\AppTestCase;

class PharAdapterTest extends AppTestCase
{
    private PharAdapter $pharArchive;
    private string $archiveName;

    public function setUp(): void
    {
        parent::setUp();

        $this->archiveName = base_dir() . DS . 'test.phar';
        $this->pharArchive = new PharAdapter();
        $this->pharArchive->setName($this->archiveName);
    }

    public function tearDown(): void
    {
        if ($this->fs->exists($this->archiveName)) {
            $this->fs->remove($this->archiveName);
            $this->pharArchive->removeArchive();
        }

        parent::tearDown();
    }

    public function testAddEmptyDirToPhar(): void
    {
        $this->assertTrue($this->pharArchive->addEmptyDir('directoryOne'));

        $this->assertTrue($this->pharArchive->offsetExists('directoryOne'));

        $this->assertDirectoryExists("phar://{$this->archiveName}/directoryOne");
    }

    public function testAddFileToPhar(): void
    {
        $this->assertFalse($this->pharArchive->offsetExists('app.conf'));

        $this->assertTrue(
            $this->pharArchive->addFile(
                base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
                'app.conf'
            )
        );

        $this->assertTrue($this->pharArchive->offsetExists('app.conf'));

        $this->assertFileExists("phar://{$this->archiveName}/app.conf");
    }

    public function testAddFromStringToPhar(): void
    {
        $this->assertTrue($this->pharArchive->addFromString('custom.text', 'Just a sample text'));

        $this->assertTrue($this->pharArchive->offsetExists('custom.text'));

        $this->assertStringEqualsFile("phar://{$this->archiveName}/custom.text", 'Just a sample text');
    }

    public function testAddMultipleFilesToPhar(): void
    {
        $this->assertTrue($this->pharArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]));

        $this->assertTrue($this->pharArchive->offsetExists('app.conf'));

        $this->assertTrue($this->pharArchive->offsetExists('session.conf'));
    }

    public function testPharCount(): void
    {
        $this->assertTrue($this->pharArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]));

        $this->assertEquals(2, $this->pharArchive->count());
    }

    public function testExtractToFromPhar(): void
    {
        $content = 'Just a sample text';

        $this->pharArchive->addFromString('sample.txt', $content);

        $this->assertTrue($this->pharArchive->extractTo(base_dir()));

        $this->assertFileExists(base_dir() . DS . 'sample.txt');

        $this->assertEquals($content, $this->fs->get(base_dir() . DS . 'sample.txt'));

        $this->fs->remove(base_dir() . DS . 'sample.txt');
    }

    public function testDeleteFileFromPhar(): void
    {
        $this->pharArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]);

        $this->assertEquals(2, $this->pharArchive->count());

        $this->pharArchive->addFromString('sample.txt', 'Just a sample text');

        $this->assertEquals(3, $this->pharArchive->count());

        $this->pharArchive->deleteFile('sample.txt');

        $this->assertFalse($this->pharArchive->offsetExists('sample.txt'));

        $this->assertEquals(2, $this->pharArchive->count());
    }

    public function testDeleteMultipleFilesUsingNameFromPhar(): void
    {
        $this->pharArchive->addMultipleFiles([
            'app.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'app.php',
            'directoryOne/session.conf' => base_dir() . DS . 'shared' . DS . 'config' . DS . 'session.php',
        ]);

        $this->assertEquals(2, $this->pharArchive->count());

        $this->assertTrue($this->pharArchive->offsetExists('app.conf'));
        $this->assertTrue($this->pharArchive->offsetExists('directoryOne/session.conf'));

        $this->assertTrue($this->pharArchive->deleteMultipleFiles([
            'directoryOne/session.conf',
            'app.conf',
        ]));

        $this->assertEquals(0, $this->pharArchive->count());

        $this->assertFalse($this->pharArchive->offsetExists('directoryOne/session.conf'));

        $this->assertFalse($this->pharArchive->offsetExists('app.conf'));
    }
}
