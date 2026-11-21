<?php

namespace Quantum\Tests\Unit\Cache\Adapters;

use Quantum\Cache\Exceptions\InvalidCacheKeyException;
use Psr\SimpleCache\InvalidArgumentException;
use Quantum\Cache\Adapters\FileAdapter;
use Quantum\Tests\Unit\AppTestCase;
use DateInterval;

class FileAdapterTest extends AppTestCase
{
    private FileAdapter $fileCache;

    public function setUp(): void
    {
        parent::setUp();

        $params = [
            'prefix' => 'test',
            'path' => base_dir() . DS . 'cache' . DS . 'data',
            'ttl' => 60,
        ];

        $this->fileCache = new FileAdapter($params);
    }

    public function tearDown(): void
    {
        $this->fileCache->clear();

        parent::tearDown();
    }

    public function testFileAdapterSetGetDelete(): void
    {
        $this->assertNull($this->fileCache->get('test'));

        $this->assertNotNull($this->fileCache->get('test', 'Some default value'));

        $this->assertEquals('Some default value', $this->fileCache->get('test', 'Some default value'));

        $this->fileCache->set('test', 'Test value');

        $this->assertNotNull($this->fileCache->get('test'));

        $this->assertEquals('Test value', $this->fileCache->get('test'));

        $this->fileCache->delete('test');

        $this->assertNull($this->fileCache->get('test'));
    }

    public function testFileAdapterHas(): void
    {
        $this->assertFalse($this->fileCache->has('test'));

        $this->fileCache->set('test', 'Some value');

        $this->assertTrue($this->fileCache->has('test'));
    }

    public function testFileAdapterGetMultiple(): void
    {
        $cacheItems = $this->fileCache->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNull($cacheItems['test1']);

        $cacheItems = $this->fileCache->getMultiple(['test1', 'test2'], 'Default value for all');

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNotNull($cacheItems['test1']);

        $this->assertEquals('Default value for all', $cacheItems['test1']);
    }

    public function testFileAdapterSetMultiple(): void
    {
        $this->assertFalse($this->fileCache->has('test1'));

        $this->assertFalse($this->fileCache->has('test2'));

        $this->fileCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->fileCache->has('test1'));

        $this->assertEquals('Test value one', $this->fileCache->get('test1'));

        $this->assertTrue($this->fileCache->has('test2'));

        $this->assertEquals('Test value two', $this->fileCache->get('test2'));
    }

    public function testFileAdapterDeleteMultiple(): void
    {
        $this->fileCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->fileCache->has('test1'));

        $this->assertTrue($this->fileCache->has('test2'));

        $this->fileCache->deleteMultiple(['test1', 'test2']);

        $this->assertFalse($this->fileCache->has('test1'));

        $this->assertFalse($this->fileCache->has('test2'));
    }

    public function testFileAdapterClear(): void
    {
        $this->fileCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->fileCache->has('test1'));

        $this->assertTrue($this->fileCache->has('test2'));

        $this->fileCache->clear();

        $this->assertFalse($this->fileCache->has('test1'));

        $this->assertFalse($this->fileCache->has('test2'));
    }

    public function testFileAdapterExpired(): void
    {
        $params = [
            'prefix' => 'test',
            'path' => base_dir() . DS . 'cache' . DS . 'data',
            'ttl' => -1,
        ];

        $fileCache = new FileAdapter($params);

        $fileCache->set('test', 'Test value');

        $this->assertNull($fileCache->get('test'));
    }

    public function testFileAdapterSetWithCustomTtl(): void
    {
        $this->fileCache->set('test', 'Test value', 120);

        $this->assertTrue($this->fileCache->has('test'));

        $this->assertEquals('Test value', $this->fileCache->get('test'));
    }

    public function testFileAdapterSetWithDateIntervalTtl(): void
    {
        $this->fileCache->set('test', 'Test value', new DateInterval('PT60S'));

        $this->assertTrue($this->fileCache->has('test'));

        $this->assertEquals('Test value', $this->fileCache->get('test'));
    }

    public function testFileAdapterExpiredWithCustomTtl(): void
    {
        $this->fileCache->set('test', 'Test value', -1);

        $this->assertNull($this->fileCache->get('test'));
    }

    public function testFileAdapterPerKeyTtlIndependence(): void
    {
        $this->fileCache->set('short', 'Short TTL', -1);

        $this->fileCache->set('long', 'Long TTL', 120);

        $this->assertFalse($this->fileCache->has('short'));

        $this->assertTrue($this->fileCache->has('long'));

        $this->assertEquals('Long TTL', $this->fileCache->get('long'));
    }

    public function testFileAdapterMultipleMethodsAcceptAnyIterable(): void
    {
        $this->assertTrue($this->fileCache->setMultiple((function () {
            yield 'test1' => 'Test value one';
            yield 'test2' => 'Test value two';
        })()));

        $this->assertSame(
            ['test1' => 'Test value one', 'test2' => 'Test value two'],
            $this->fileCache->getMultiple((function () {
                yield 'test1';
                yield 'test2';
            })())
        );

        $this->assertTrue($this->fileCache->deleteMultiple(new \ArrayIterator(['test1', 'test2'])));

        $this->assertFalse($this->fileCache->has('test1'));

        $this->assertFalse($this->fileCache->has('test2'));
    }

    public function testFileAdapterMultipleMethodsAcceptNumericKeys(): void
    {
        $this->assertTrue($this->fileCache->setMultiple(['1' => 'Value one', '2' => 'Value two']));

        $this->assertSame([1 => 'Value one', 2 => 'Value two'], $this->fileCache->getMultiple([1, 2]));

        $this->assertTrue($this->fileCache->deleteMultiple([1, 2]));

        $this->assertFalse($this->fileCache->has('1'));
    }
    public function testFileAdapterDeleteMultipleRejectsKeysThatAreNotStringsOrIntegers(): void
    {
        $this->fileCache->set('1', 'Value one');

        try {
            $this->fileCache->deleteMultiple([true]);
            $this->fail('A boolean key was accepted');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidCacheKeyException::class, $e);
            $this->assertSame('The cache key must be a string, bool given', $e->getMessage());
        }

        $this->assertTrue($this->fileCache->has('1'));
    }
}
