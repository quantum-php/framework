<?php

namespace Quantum\Tests\Unit\RateLimit\Factories;

use Quantum\RateLimit\Adapters\RedisRateLimitAdapter;
use Quantum\RateLimit\Exceptions\RateLimitException;
use Quantum\RateLimit\Adapters\FileRateLimitAdapter;
use Quantum\RateLimit\Factories\RateLimiterFactory;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\RateLimit\Enums\RateLimitType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\RateLimit\RateLimiter;
use Quantum\Config\Setup;
use Quantum\Di\Di;

class RateLimiterFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->resetRateLimiterFactory();
    }

    public function testRateLimiterFactoryReturnsRateLimiter(): void
    {
        $limiter = RateLimiterFactory::get();
        $this->assertInstanceOf(RateLimiter::class, $limiter);
    }

    public function testRateLimiterFactoryDefaultAdapterIsFile(): void
    {
        $limiter = RateLimiterFactory::get();
        $this->assertInstanceOf(FileRateLimitAdapter::class, $limiter->getAdapter());
    }

    public function testRateLimiterFactoryRedisAdapter(): void
    {
        $limiter = RateLimiterFactory::get(RateLimitType::REDIS);
        $this->assertInstanceOf(RedisRateLimitAdapter::class, $limiter->getAdapter());
    }

    public function testRateLimiterFactoryReadsTheTtlGivenAsText(): void
    {
        $this->setRateLimitConfig('file.ttl', '30');

        $limiter = RateLimiterFactory::get(RateLimitType::FILE);

        $this->assertInstanceOf(FileRateLimitAdapter::class, $limiter->getAdapter());
    }

    public function testRateLimiterFactoryRejectsAMissingTtl(): void
    {
        $this->setRateLimitConfig('file', ['prefix' => '', 'path' => base_dir() . DS . 'cache' . DS . 'data']);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `ttl` is missing.');

        RateLimiterFactory::get(RateLimitType::FILE);
    }

    public function testRateLimiterFactoryRejectsARedisPortThatIsNotANumber(): void
    {
        $this->setRateLimitConfig('redis.port', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `port` has the value `abc`, which is not a whole number.');

        RateLimiterFactory::get(RateLimitType::REDIS);
    }

    public function testRateLimiterFactoryThrowsForInvalidAdapter(): void
    {
        $this->expectException(RateLimitException::class);
        $this->expectExceptionMessage('Rate limit adapter `invalid` is not supported.');

        RateLimiterFactory::get('invalid');
    }

    public function testRateLimiterFactoryReturnsSameInstancePerAdapter(): void
    {
        $limiter1 = RateLimiterFactory::get(RateLimitType::FILE);
        $limiter2 = RateLimiterFactory::get(RateLimitType::FILE);
        $this->assertSame($limiter1, $limiter2);
    }

    private function resetRateLimiterFactory(): void
    {
        if (!Di::isRegistered(RateLimiterFactory::class)) {
            Di::register(RateLimiterFactory::class);
        }

        $factory = Di::get(RateLimiterFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }

    private function setRateLimitConfig(string $key, mixed $value): void
    {
        if (!config()->has('rate_limit')) {
            config()->import(new Setup('config', 'rate_limit'));
        }

        config()->set('rate_limit.' . $key, $value);
    }
}
