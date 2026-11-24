<?php

namespace Quantum\Tests\Unit\RateLimit\Adapters;

use Quantum\RateLimit\Adapters\FileRateLimitAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;

class FileRateLimitAdapterTest extends AppTestCase
{
    private string $rateLimitDir;

    public function setUp(): void
    {
        parent::setUp();
        $this->rateLimitDir = base_dir() . DS . 'cache' . DS . 'rate_limit_tests';
        if (!fs()->isDirectory($this->rateLimitDir)) {
            fs()->makeDirectory($this->rateLimitDir);
        }
    }

    public function tearDown(): void
    {
        $files = fs()->glob($this->rateLimitDir . DS . '*') ?: [];
        foreach ($files as $file) {
            fs()->remove($file);
        }
        if (fs()->isDirectory($this->rateLimitDir)) {
            fs()->removeDirectory($this->rateLimitDir);
        }

        parent::tearDown();
    }

    public function testFileAdapterHitAndResetFlow(): void
    {
        $adapter = new FileRateLimitAdapter(new ConfigSection([
            'ttl' => 30,
            'prefix' => 'test',
            'path' => $this->rateLimitDir,
        ]));

        $this->assertTrue($adapter->hit('k1', 2, 60));
        $this->assertTrue($adapter->hit('k1', 2, 60));
        $this->assertFalse($adapter->hit('k1', 2, 60));

        $adapter->reset('k1');
        $this->assertTrue($adapter->hit('k1', 2, 60));
    }

    public function testFileAdapterRepeatedHitsRespectExactLimitBoundary(): void
    {
        $adapter = new FileRateLimitAdapter(new ConfigSection([
            'ttl' => 30,
            'prefix' => 'test',
            'path' => $this->rateLimitDir,
        ]));

        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue($adapter->hit('boundary', 100, 60));
        }

        $this->assertFalse($adapter->hit('boundary', 100, 60));
    }

    public function testFileAdapterCreatesStorageDirectoryWhenMissing(): void
    {
        $path = base_dir() . DS . 'cache' . DS . 'rate_limit_tests_missing';

        if (fs()->isDirectory($path)) {
            fs()->removeDirectory($path);
        }

        try {
            new FileRateLimitAdapter(new ConfigSection([
                'ttl' => 30,
                'prefix' => 'test',
                'path' => $path,
            ]));

            $this->assertTrue(fs()->isDirectory($path));
        } finally {
            if (fs()->isDirectory($path)) {
                fs()->removeDirectory($path);
            }
        }
    }

    public function testFileAdapterCreatesMissingParentDirectories(): void
    {
        $root = base_dir() . DS . 'cache' . DS . 'rate_limit_tests_nested';
        $path = $root . DS . 'level_one' . DS . 'level_two';

        deleteDirectoryWithFiles($root);

        try {
            new FileRateLimitAdapter(new ConfigSection([
                'ttl' => 30,
                'prefix' => 'test',
                'path' => $path,
            ]));

            $this->assertTrue(fs()->isDirectory($path));
        } finally {
            deleteDirectoryWithFiles($root);
        }
    }

    public function testFileAdapterRetryAfterReturnsZeroForMissingOrInvalidState(): void
    {
        $adapter = new FileRateLimitAdapter(new ConfigSection([
            'ttl' => 30,
            'prefix' => 'test',
            'path' => $this->rateLimitDir,
        ]));

        $this->assertSame(0, $adapter->retryAfter('missing-key'));

        $adapter->hit('broken', 1, 60);
        $stateFiles = fs()->glob($this->rateLimitDir . DS . '*.rate') ?: [];
        $this->assertCount(1, $stateFiles);

        fs()->put((string) current($stateFiles), '{invalid-json');

        $this->assertSame(0, $adapter->retryAfter('broken'));
    }
}
