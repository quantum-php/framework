<?php

namespace Quantum\Tests\Unit\Cache\Adapters;

use Quantum\Database\Adapters\Sleekdb\SleekDbal;
use Quantum\Cache\Adapters\DatabaseAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\Setup;
use DateInterval;

class DatabaseAdapterTest extends AppTestCase
{
    private DatabaseAdapter $databaseCache;

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database'));
        }

        config()->set('database.default', 'sleekdb');

        SleekDbal::connect(config()->get('database.sleekdb'));

        $params = [
            'prefix' => 'test',
            'table' => 'cache',
            'ttl' => 60,
        ];

        $this->databaseCache = new DatabaseAdapter($params);
    }

    public function tearDown(): void
    {
        $this->databaseCache->clear();

        SleekDbal::disconnect();
    }

    public function testDatabaseAdapterSetGetDelete(): void
    {

        $this->assertNull($this->databaseCache->get('test'));

        $this->assertNotNull($this->databaseCache->get('test', 'Some default value'));

        $this->assertEquals('Some default value', $this->databaseCache->get('test', 'Some default value'));

        $this->databaseCache->set('test', 'Test value');

        $this->assertNotNull($this->databaseCache->get('test'));

        $this->assertEquals('Test value', $this->databaseCache->get('test'));

        $this->databaseCache->delete('test');

        $this->assertNull($this->databaseCache->get('test'));
    }

    public function testDatabaseAdapterHas(): void
    {
        $this->assertFalse($this->databaseCache->has('test'));

        $this->databaseCache->set('test', 'Some value');

        $this->assertTrue($this->databaseCache->has('test'));
    }

    public function testDatabaseAdapterGetMultiple(): void
    {
        $cacheItems = $this->databaseCache->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNull($cacheItems['test1']);

        $cacheItems = $this->databaseCache->getMultiple(['test1', 'test2'], 'Default value for all');

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNotNull($cacheItems['test1']);

        $this->assertEquals('Default value for all', $cacheItems['test1']);

        $this->databaseCache->set('test1', 'Test one');

        $this->databaseCache->set('test2', 'Test two');

        $cacheItems = $this->databaseCache->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertEquals('Test one', $cacheItems['test1']);
    }

    public function testDatabaseAdapterSetMultiple(): void
    {
        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));

        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertEquals('Test value one', $this->databaseCache->get('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->assertEquals('Test value two', $this->databaseCache->get('test2'));
    }

    public function testDatabaseAdapterDeleteMultiple(): void
    {
        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->databaseCache->deleteMultiple(['test1', 'test2']);

        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));
    }

    public function testDatabaseAdapterClear(): void
    {
        $this->databaseCache->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->databaseCache->has('test1'));

        $this->assertTrue($this->databaseCache->has('test2'));

        $this->databaseCache->clear();

        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));
    }

    public function testDatabaseAdapterExpired(): void
    {
        $params = [
            'prefix' => 'test',
            'table' => 'cache',
            'ttl' => -1,
        ];

        $databaseCache = new DatabaseAdapter($params);

        $databaseCache->set('test', 'Test value');

        $this->assertNull($databaseCache->get('test'));
    }

    public function testDatabaseAdapterSetWithCustomTtl(): void
    {
        $this->databaseCache->set('test', 'Test value', 120);

        $this->assertTrue($this->databaseCache->has('test'));

        $this->assertEquals('Test value', $this->databaseCache->get('test'));
    }

    public function testDatabaseAdapterSetWithDateIntervalTtl(): void
    {
        $this->databaseCache->set('test', 'Test value', new DateInterval('PT60S'));

        $this->assertTrue($this->databaseCache->has('test'));

        $this->assertEquals('Test value', $this->databaseCache->get('test'));
    }

    public function testDatabaseAdapterExpiredWithCustomTtl(): void
    {
        $this->databaseCache->set('test', 'Test value', -1);

        $this->assertNull($this->databaseCache->get('test'));
    }

    public function testDatabaseAdapterPerKeyTtlIndependence(): void
    {
        $this->databaseCache->set('short', 'Short TTL', -1);

        $this->databaseCache->set('long', 'Long TTL', 120);

        $this->assertFalse($this->databaseCache->has('short'));

        $this->assertTrue($this->databaseCache->has('long'));

        $this->assertEquals('Long TTL', $this->databaseCache->get('long'));
    }

    public function testDatabaseAdapterMultipleMethodsAcceptAnyIterable(): void
    {
        $this->assertTrue($this->databaseCache->setMultiple((function () {
            yield 'test1' => 'Test value one';
            yield 'test2' => 'Test value two';
        })()));

        $this->assertSame(
            ['test1' => 'Test value one', 'test2' => 'Test value two'],
            $this->databaseCache->getMultiple((function () {
                yield 'test1';
                yield 'test2';
            })())
        );

        $this->assertTrue($this->databaseCache->deleteMultiple(new \ArrayIterator(['test1', 'test2'])));

        $this->assertFalse($this->databaseCache->has('test1'));

        $this->assertFalse($this->databaseCache->has('test2'));
    }

    public function testDatabaseAdapterMultipleMethodsAcceptNumericKeys(): void
    {
        $this->assertTrue($this->databaseCache->setMultiple(['1' => 'Value one', '2' => 'Value two']));

        $this->assertSame([1 => 'Value one', 2 => 'Value two'], $this->databaseCache->getMultiple([1, 2]));

        $this->assertTrue($this->databaseCache->deleteMultiple([1, 2]));

        $this->assertFalse($this->databaseCache->has('1'));
    }
}
