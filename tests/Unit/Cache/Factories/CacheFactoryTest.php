<?php

namespace Quantum\Tests\Unit\Cache\Factories;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Cache\Exceptions\CacheException;
use Quantum\Cache\Adapters\MemcachedAdapter;
use Quantum\Cache\Adapters\DatabaseAdapter;
use Quantum\Cache\Factories\CacheFactory;
use Quantum\Cache\Adapters\RedisAdapter;
use Quantum\Cache\Adapters\FileAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Cache\Enums\CacheType;
use Quantum\Config\Setup;
use Quantum\Cache\Cache;
use Quantum\Di\Di;

class CacheFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetCacheFactory();
    }

    public function testCacheFactoryInstance(): void
    {
        $cache = CacheFactory::get();

        $this->assertInstanceOf(Cache::class, $cache);
    }

    public function testCacheFactoryDefaultAdapter(): void
    {
        $cache = CacheFactory::get();

        $this->assertInstanceOf(FileAdapter::class, $cache->getAdapter());
    }

    public function testCacheFactoryFileAdapter(): void
    {
        $cache = CacheFactory::get(CacheType::FILE);

        $this->assertInstanceOf(FileAdapter::class, $cache->getAdapter());
    }

    public function testCacheFactoryDatabaseAdapter(): void
    {
        $cache = CacheFactory::get(CacheType::DATABASE);

        $this->assertInstanceOf(DatabaseAdapter::class, $cache->getAdapter());
    }

    public function testCacheFactoryMemcachedAdapter(): void
    {
        $cache = CacheFactory::get(CacheType::MEMCACHED);

        $this->assertInstanceOf(MemcachedAdapter::class, $cache->getAdapter());
    }

    public function testCacheFactoryRedisAdapter(): void
    {
        $cache = CacheFactory::get(CacheType::REDIS);

        $this->assertInstanceOf(RedisAdapter::class, $cache->getAdapter());
    }

    public function testCacheFactoryReadsTheTtlGivenAsText(): void
    {
        $this->setCacheConfig('file.ttl', '600');

        $cache = CacheFactory::get(CacheType::FILE);

        $this->assertTrue($cache->set('ttl_as_text', 'value'));
        $this->assertSame('value', $cache->get('ttl_as_text'));

        $cache->delete('ttl_as_text');
    }

    public function testCacheFactoryRejectsAMissingTtl(): void
    {
        $this->setCacheConfig('file', ['prefix' => '', 'path' => base_dir() . DS . 'cache' . DS . 'data']);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `ttl` is missing.');

        CacheFactory::get(CacheType::FILE);
    }

    public function testCacheFactoryRejectsARedisPortThatIsNotANumber(): void
    {
        $this->setCacheConfig('redis.port', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `port` has the value `abc`, which is not a whole number.');

        CacheFactory::get(CacheType::REDIS);
    }

    public function testCacheFactoryRejectsAMemcachedPortThatIsNotANumber(): void
    {
        $this->setCacheConfig('memcached.port', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `port` has the value `abc`, which is not a whole number.');

        CacheFactory::get(CacheType::MEMCACHED);
    }

    public function testCacheFactoryInvalidTypeAdapter(): void
    {
        $this->expectException(CacheException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        CacheFactory::get('invalid_type');
    }

    public function testCacheFactoryReturnsSameInstance(): void
    {
        $cache1 = CacheFactory::get(CacheType::FILE);
        $cache2 = CacheFactory::get(CacheType::FILE);

        $this->assertSame($cache1, $cache2);
    }

    private function resetCacheFactory(): void
    {
        if (!Di::isRegistered(CacheFactory::class)) {
            Di::register(CacheFactory::class);
        }

        $factory = Di::get(CacheFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }

    private function setCacheConfig(string $key, mixed $value): void
    {
        if (!config()->has('cache')) {
            config()->import(new Setup('config', 'cache'));
        }

        config()->set('cache.' . $key, $value);
    }
}
