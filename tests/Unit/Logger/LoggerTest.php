<?php

namespace Quantum\Tests\Unit\Logger;

use Quantum\Logger\Contracts\ReportableInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Logger\Logger;
use Psr\Log\LogLevel;
use Stringable;

class LoggerTest extends AppTestCase
{
    private Logger $logger;
    private MessageAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new MessageAdapter();

        $this->logger = new Logger($this->adapter);
    }

    public function testLoggerGetAdapter(): void
    {
        $this->assertEquals($this->adapter, $this->logger->getAdapter());

        $this->assertInstanceOf(MessageAdapter::class, $this->logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $this->logger->getAdapter());
    }

    public function testLoggerLogReportsTheMessageToTheAdapter(): void
    {
        $this->logger->log('info', 'Test message', []);

        $this->assertSame([['info' => 'Test message']], $this->adapter->getMessages());

        $this->logger->log('error', 'Error message', []);

        $this->assertSame([['info' => 'Test message'], ['error' => 'Error message']], $this->adapter->getMessages());
    }

    #[DataProvider('levelMethodProvider')]
    public function testLoggerLevelMethodReportsTheMessageWithItsLevel(string $method, string $level): void
    {
        $this->logger->$method('Some message');

        $this->assertSame([[$level => 'Some message']], $this->adapter->getMessages());
    }

    public function testLoggerAcceptsAStringableMessage(): void
    {
        $message = new class () implements Stringable {
            public function __toString(): string
            {
                return 'From an object';
            }
        };

        $this->logger->critical($message);

        $this->assertSame([['critical' => $message]], $this->adapter->getMessages());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function levelMethodProvider(): array
    {
        return [
            'emergency' => ['emergency', LogLevel::EMERGENCY],
            'alert' => ['alert', LogLevel::ALERT],
            'critical' => ['critical', LogLevel::CRITICAL],
            'error' => ['error', LogLevel::ERROR],
            'warning' => ['warning', LogLevel::WARNING],
            'notice' => ['notice', LogLevel::NOTICE],
            'info' => ['info', LogLevel::INFO],
            'debug' => ['debug', LogLevel::DEBUG],
        ];
    }
}
