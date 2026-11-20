<?php

namespace Quantum\Tests\Unit\RateLimit\Adapters;

use Quantum\RateLimit\Adapters\RedisRateLimitAdapter;
use Quantum\Tests\Unit\AppTestCase;

class RedisRateLimitAdapterTest extends AppTestCase
{
    private RedisRateLimitAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new RedisRateLimitAdapter([
            'host' => '127.0.0.1',
            'port' => 6379,
            'ttl' => 30,
        ]);
    }

    public function tearDown(): void
    {
        $this->adapter->reset('k2');

        parent::tearDown();
    }

    public function testRedisAdapterConnectsWithThePortAsText(): void
    {
        $adapter = new RedisRateLimitAdapter([
            'host' => '127.0.0.1',
            'port' => '6379',
            'ttl' => 30,
        ]);

        $this->assertTrue($adapter->hit('k2', 5, 30));
    }

    public function testRedisAdapterHitAndResetFlow(): void
    {
        $this->assertTrue($this->adapter->hit('k2', 1, 60));
        $this->assertFalse($this->adapter->hit('k2', 1, 60));

        $this->adapter->reset('k2', 1);
        $this->assertFalse($this->adapter->hit('k2', 1, 60));
    }

    public function testRedisAdapterRetryAfterReturnsZeroForMissingKey(): void
    {
        $this->adapter->reset('missing-key');
        $this->assertSame(0, $this->adapter->retryAfter('missing-key'));
    }
}
