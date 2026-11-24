<?php

namespace Quantum\Tests\Unit\Logger\Adapters;

use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Logger\Adapters\DailyAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;

class DailyAdapterTest extends AppTestCase
{
    private DailyAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new DailyAdapter(new ConfigSection([
            'path' => base_dir() . DS . 'logs',
        ]));
    }

    public function testDailyAdapterInstance(): void
    {
        $this->assertInstanceOf(DailyAdapter::class, $this->adapter);
    }

    public function testDailyAdapterConstructorThrowsExceptionForInvalidPath(): void
    {
        $this->expectException(LoggerException::class);

        new DailyAdapter(new ConfigSection([
            'path' => base_dir() . DS . 'invalid_path',
        ]));
    }

    public function testDailyAdapterReportWritesToFile(): void
    {
        $level = 'info';
        $message = 'Test log message';
        $context = [];

        $this->adapter->report($level, $message, $context);

        $logFile = base_dir() . DS . 'logs' . DS . date('Y-m-d') . '.log';

        $this->assertTrue($this->fs->exists($logFile));

        $logContent = $this->fs->get($logFile);
        $this->assertStringContainsString('Test log message', $logContent);

        $this->fs->remove($logFile);
    }

    public function testDailyAdapterWritesTheContextAsJsonAfterTheMessage(): void
    {
        $this->adapter->report('info', 'Order paid', ['order' => 1042]);

        $logFile = base_dir() . DS . 'logs' . DS . date('Y-m-d') . '.log';

        $logContent = $this->fs->get($logFile);

        $this->fs->remove($logFile);

        $this->assertStringContainsString('Info: Order paid {"order":1042}' . PHP_EOL, $logContent);
    }
}
