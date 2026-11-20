<?php

namespace Quantum\Tests\Unit\Cache\Adapters;

use Quantum\Cache\Adapters\RedisAdapter;
use Quantum\Tests\Unit\AppTestCase;
use DateInterval;

class RedisAdapterTest extends AppTestCase
{
    private RedisAdapter $redis;

    public function setUp(): void
    {
        parent::setUp();

        $params = [
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => 6379,
            'ttl' => 60,
        ];

        $this->redis = new RedisAdapter($params);
    }

    public function tearDown(): void
    {
        $this->redis->clear();

        parent::tearDown();
    }

    public function testRedisAdapterConnectsWithThePortAsText(): void
    {
        $adapter = new RedisAdapter([
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => '6379',
            'ttl' => 60,
        ]);

        $adapter->set('port-as-text', 'connected');

        $this->assertSame('connected', $adapter->get('port-as-text'));
    }

    public function testRedisAdapterSetGetDelete(): void
    {

        $this->assertNull($this->redis->get('test'));

        $this->assertNotNull($this->redis->get('test', 'Some default value'));

        $this->assertEquals('Some default value', $this->redis->get('test', 'Some default value'));

        $this->redis->set('test', 'Test value');

        $this->assertNotNull($this->redis->get('test'));

        $this->assertEquals('Test value', $this->redis->get('test'));

        $this->redis->delete('test');

        $this->assertNull($this->redis->get('test'));
    }

    public function testRedisAdapterHas(): void
    {
        $this->assertFalse($this->redis->has('test'));

        $this->redis->set('test', 'Some value');

        $this->assertTrue($this->redis->has('test'));
    }

    public function testRedisAdapterGetMultiple(): void
    {
        $cacheItems = $this->redis->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNull($cacheItems['test1']);

        $cacheItems = $this->redis->getMultiple(['test1', 'test2'], 'Default value for all');

        $this->assertIsArray($cacheItems);

        $this->assertArrayHasKey('test1', $cacheItems);

        $this->assertNotNull($cacheItems['test1']);

        $this->assertEquals('Default value for all', $cacheItems['test1']);

        $this->redis->set('test1', 'Test one');

        $this->redis->set('test2', 'Test two');

        $cacheItems = $this->redis->getMultiple(['test1', 'test2']);

        $this->assertIsArray($cacheItems);

        $this->assertEquals('Test one', $cacheItems['test1']);
    }

    public function testRedisAdapterSetMultiple(): void
    {
        $this->assertFalse($this->redis->has('test1'));

        $this->assertFalse($this->redis->has('test2'));

        $this->redis->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->redis->has('test1'));

        $this->assertEquals('Test value one', $this->redis->get('test1'));

        $this->assertTrue($this->redis->has('test2'));

        $this->assertEquals('Test value two', $this->redis->get('test2'));
    }

    public function testRedisAdapterDeleteMultiple(): void
    {
        $this->redis->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->redis->has('test1'));

        $this->assertTrue($this->redis->has('test2'));

        $this->redis->deleteMultiple(['test1', 'test2']);

        $this->assertFalse($this->redis->has('test1'));

        $this->assertFalse($this->redis->has('test2'));
    }

    public function testRedisAdapterClear(): void
    {
        $this->redis->setMultiple(['test1' => 'Test value one', 'test2' => 'Test value two']);

        $this->assertTrue($this->redis->has('test1'));

        $this->assertTrue($this->redis->has('test2'));

        $this->redis->clear();

        $this->assertFalse($this->redis->has('test1'));

        $this->assertFalse($this->redis->has('test2'));
    }

    public function testRedisAdapterExpired(): void
    {
        $params = [
            'prefix' => 'test',
            'host' => '127.0.0.1',
            'port' => 6379,
            'ttl' => 1,
        ];

        $redis = new RedisAdapter($params);

        $redis->set('test', 'Test value');

        sleep(2);

        $this->assertNull($redis->get('test'));
    }

    public function testRedisAdapterSetWithCustomTtl(): void
    {
        $this->redis->set('test', 'Test value', 120);

        $this->assertTrue($this->redis->has('test'));

        $this->assertEquals('Test value', $this->redis->get('test'));
    }

    public function testRedisAdapterSetWithNonPositiveTtlDeletesExistingKey(): void
    {
        foreach ([0, -1] as $ttl) {
            $key = 'non_positive_ttl_' . $ttl;
            $this->redis->set($key, 'Existing value');

            $this->assertTrue($this->redis->set($key, 'Expired value', $ttl));
            $this->assertFalse($this->redis->has($key));
        }
    }

    public function testRedisAdapterSetWithDateIntervalTtl(): void
    {
        $this->redis->set('test', 'Test value', new DateInterval('PT60S'));

        $this->assertTrue($this->redis->has('test'));

        $this->assertEquals('Test value', $this->redis->get('test'));
    }

    public function testRedisAdapterExpiredWithCustomTtl(): void
    {
        $this->redis->set('test', 'Test value', 1);

        sleep(2);

        $this->assertNull($this->redis->get('test'));
    }

}
