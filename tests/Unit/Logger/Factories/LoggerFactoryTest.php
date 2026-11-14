<?php

namespace Quantum\Tests\Unit\Logger\Factories;

use Quantum\Logger\Contracts\ReportableInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Logger\Factories\LoggerFactory;
use Quantum\Logger\Adapters\SingleAdapter;
use Quantum\Logger\Adapters\DailyAdapter;
use Quantum\Logger\Enums\LoggerType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Enums\AppType;
use Quantum\Logger\Logger;
use Quantum\Di\Di;

class LoggerFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('app.debug', false);

        $this->resetLoggerFactory();
    }

    public function testLoggerFactoryInstance(): void
    {
        $logger = LoggerFactory::get();

        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testLoggerFactoryGetDefaultAdapter(): void
    {
        $logger = LoggerFactory::get();

        $this->assertInstanceOf(SingleAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryGetSingleAdapter(): void
    {
        $logger = LoggerFactory::get(LoggerType::SINGLE);

        $this->assertInstanceOf(SingleAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryGetDailyAdapter(): void
    {
        $logger = LoggerFactory::get(LoggerType::DAILY);

        $this->assertInstanceOf(DailyAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryGetMessageAdapter(): void
    {
        config()->set('app.debug', true);

        $logger = LoggerFactory::get();

        $this->assertInstanceOf(MessageAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryPassesTheMessageLimitFromTheConfig(): void
    {
        config()->set('logging.message.limit', 2);
        config()->set('app.debug', true);

        $logger = LoggerFactory::get();

        $logger->info('First');
        $logger->info('Second');
        $logger->info('Third');

        $this->assertSame([['info' => 'Second'], ['info' => 'Third']], $logger->getAdapter()->getMessages());
    }

    public function testLoggerFactoryThrowsForMessageLimitThatIsNotAWholeNumber(): void
    {
        config()->set('logging.message.limit', 'abc');
        config()->set('app.debug', true);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `logging.message.limit` has the value `abc`, which is not a whole number.');

        LoggerFactory::get();
    }

    public function testLoggerFactoryUsesTheDefaultMessageLimitWithoutTheKey(): void
    {
        config()->set('logging.message', []);
        config()->set('app.debug', true);

        $logger = LoggerFactory::get();

        for ($i = 1; $i <= 1001; $i++) {
            $logger->info('Message ' . $i);
        }

        $this->assertCount(1000, $logger->getAdapter()->getMessages());
    }

    public function testLoggerFactoryInConsoleDebugModeGetsTheDefaultAdapter(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', true);

        $this->assertInstanceOf(SingleAdapter::class, LoggerFactory::get()->getAdapter());
    }

    public function testLoggerFactoryInConsoleDebugModeGetsTheRequestedAdapter(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', true);

        $this->assertInstanceOf(DailyAdapter::class, LoggerFactory::get(LoggerType::DAILY)->getAdapter());
    }

    public function testLoggerFactoryInConsoleDebugModeGetsTheMessageAdapterWhenAsked(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', true);

        $this->assertInstanceOf(MessageAdapter::class, LoggerFactory::get(LoggerType::MESSAGE)->getAdapter());
    }

    public function testLoggerFactoryTryingToGetMessageAdapter(): void
    {
        $this->expectException(LoggerException::class);

        $this->expectExceptionMessage('The adapter `message` is not supported.');

        LoggerFactory::get(LoggerType::MESSAGE);
    }

    public function testLoggerFactoryInvalidTypeAdapter(): void
    {
        $this->expectException(LoggerException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        LoggerFactory::get('invalid_type');
    }

    public function testLoggerFactoryReturnsSameInstance(): void
    {
        $logger1 = LoggerFactory::get(LoggerType::SINGLE);
        $logger2 = LoggerFactory::get(LoggerType::SINGLE);

        $this->assertSame($logger1, $logger2);
    }

    private function resetLoggerFactory(): void
    {
        if (!Di::isRegistered(LoggerFactory::class)) {
            Di::register(LoggerFactory::class);
        }

        $factory = Di::get(LoggerFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
