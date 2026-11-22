<?php

namespace Quantum\Tests\Unit\Cache\Adapters;

use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Cache\Adapters\MemcachedAdapter;
use Quantum\Tests\Unit\AppTestCase;
use DateInterval;
use Memcached;

class MemcachedAdapterTest extends AppTestCase
{
    private MemcachedAdapter $memCached;

    public function setUp(): void
    {
        parent::setUp();

        $params = [
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => 11211,
            'ttl' => 60,
        ];

        $this->memCached = new MemcachedAdapter($params);
    }

    public function tearDown(): void
    {
        $this->memCached->clear();

        parent::tearDown();
    }

    public function testMemcachedAdapterConnectsWithThePortAsText(): void
    {
        $adapter = new MemcachedAdapter([
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => '11211',
            'ttl' => 60,
        ]);

        $adapter->set('port-as-text', 'connected');

        $this->assertSame('connected', $adapter->get('port-as-text'));
    }

    public function testMemcachedAdapterSetGetDelete(): void
    {

        $this->assertNull($this->memCached->get('test'));

        $this->assertNotNull($this->memCached->get('test', 'Some default value'));

        $this->assertEquals('Some default value', $this->memCached->get('test', 'Some default value'));

        $this->memCached->set('test', 'Test value');

        $this->assertNotNull($this->memCached->get('test'));

        $this->assertEquals('Test value', $this->memCached->get('test'));

        $this->memCached->delete('test');

        $this->assertNull($this->memCached->get('test'));
    }

    public function testMemcachedAdapterHas(): void
    {
        $this->assertFalse($this->memCached->has('test'));

        $this->memCached->set('test', 'Some value');

        $this->assertTrue($this->memCached->has('test'));
    }

    public function testMemcachedAdapterGetMultiple(): void
    {
        $cacheItems = $this->memCached->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNull($cacheItems['test1']);

        $cacheItems = $this->memCached->getMultiple(['test1', 'test2'], 'Default value for all');

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNotNull($cacheItems['test1']);

        $this->assertEquals('Default value for all', $cacheItems['test1']);

        $this->memCached->set('test1', 'Test one');

        $this->memCached->set('test2', 'Test two');

        $cacheItems = $this->memCached->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertEquals('Test one', $cacheItems['test1']);
    }

    public function testMemcachedAdapterSetMultiple(): void
    {
        $this->assertFalse($this->memCached->has('test1'));

        $this->assertFalse($this->memCached->has('test2'));

        $this->memCached->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->memCached->has('test1'));

        $this->assertEquals('Test value one', $this->memCached->get('test1'));

        $this->assertTrue($this->memCached->has('test2'));

        $this->assertEquals('Test value two', $this->memCached->get('test2'));
    }

    public function testMemcachedAdapterDeleteMultiple(): void
    {
        $this->memCached->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->memCached->has('test1'));

        $this->assertTrue($this->memCached->has('test2'));

        $this->memCached->deleteMultiple(['test1', 'test2']);

        $this->assertFalse($this->memCached->has('test1'));

        $this->assertFalse($this->memCached->has('test2'));
    }

    public function testMemcachedAdapterClear(): void
    {
        $this->memCached->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->memCached->has('test1'));

        $this->assertTrue($this->memCached->has('test2'));

        $this->memCached->clear();

        $this->assertFalse($this->memCached->has('test1'));

        $this->assertFalse($this->memCached->has('test2'));
    }

    public function testMemcachedAdapterExpired(): void
    {
        $params = [
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => 11211,
            'ttl' => 1,
        ];

        $memCached = new MemcachedAdapter($params);

        $memCached->set('test', 'Test value');

        sleep(2);

        $this->assertNull($memCached->get('test'));
    }

    public function testMemcachedAdapterSetWithCustomTtl(): void
    {
        $this->memCached->set('test', 'Test value', 120);

        $this->assertTrue($this->memCached->has('test'));

        $this->assertEquals('Test value', $this->memCached->get('test'));
    }

    public function testMemcachedAdapterSetWithDateIntervalTtl(): void
    {
        $this->memCached->set('test', 'Test value', new DateInterval('PT60S'));

        $this->assertTrue($this->memCached->has('test'));

        $this->assertEquals('Test value', $this->memCached->get('test'));
    }

    public function testMemcachedAdapterExpiredWithCustomTtl(): void
    {
        $this->memCached->set('test', 'Test value', 1);

        sleep(2);

        $this->assertNull($this->memCached->get('test'));
    }

    #[DataProvider('expiredTtlProvider')]
    public function testMemcachedAdapterDoesNotKeepAnItemWithAnExpiredTtl(int|DateInterval $ttl): void
    {
        $this->assertTrue($this->memCached->set('test', 'Test value', $ttl));

        $this->assertFalse($this->memCached->has('test'));

        $this->assertSame('Default', $this->memCached->get('test', 'Default'));
    }

    public static function expiredTtlProvider(): array
    {
        $past = new DateInterval('PT60S');
        $past->invert = 1;

        return [
            'negative seconds' => [-1],
            'zero seconds' => [0],
            'interval in the past' => [$past],
        ];
    }

    public function testMemcachedAdapterRemovesAnExistingItemWhenSetWithAnExpiredTtl(): void
    {
        $this->memCached->set('test', 'Test value', 120);

        $this->assertTrue($this->memCached->set('test', 'New value', -1));

        $this->assertFalse($this->memCached->has('test'));
    }

    public function testMemcachedAdapterReportsAFailedRemovalForAnExpiredTtl(): void
    {
        $unreachable = new Memcached();
        $unreachable->addServer('127.0.0.1', 1);

        $this->setPrivateProperty($this->memCached, 'memcached', $unreachable);

        $this->assertFalse($this->memCached->set('test', 'Test value', -1));
    }

    public function testMemcachedAdapterSetMultipleWithAnExpiredTtlKeepsNothing(): void
    {
        $this->assertTrue($this->memCached->setMultiple(['test1' => 'One', 'test2' => 'Two'], -1));

        $this->assertFalse($this->memCached->has('test1'));

        $this->assertFalse($this->memCached->has('test2'));
    }

    #[DataProvider('longTtlProvider')]
    public function testMemcachedAdapterKeepsAnItemWithATtlOfMoreThanThirtyDays(int|DateInterval $ttl): void
    {
        $this->assertTrue($this->memCached->set('test', 'Test value', $ttl));

        $this->assertSame('Test value', $this->memCached->get('test'));
    }

    public static function longTtlProvider(): array
    {
        return [
            'exactly thirty days' => [2592000],
            'thirty days and one second' => [2592001],
            'interval of 31 days' => [new DateInterval('P31D')],
        ];
    }

}
