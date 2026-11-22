<?php

namespace Quantum\Tests\Unit\Logger\Adapters;

use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Tests\Unit\AppTestCase;

class MessageAdapterTest extends AppTestCase
{
    private MessageAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new MessageAdapter();
    }

    public function testGetMessagesIsEmptyBeforeAnyReport(): void
    {
        $this->assertSame([], $this->adapter->getMessages());
    }

    public function testReportKeepsTheMessagesInOrder(): void
    {
        $this->adapter->report('info', 'Test message');
        $this->adapter->report('error', 'Error message');

        $this->assertSame([['info' => 'Test message'], ['error' => 'Error message']], $this->adapter->getMessages());
    }

    public function testReportKeepsOnlyTheLastMessagesOverTheLimit(): void
    {
        $adapter = new MessageAdapter(2);

        $adapter->report('info', 'First');
        $adapter->report('info', 'Second');
        $adapter->report('info', 'Third');

        $this->assertSame([['info' => 'Second'], ['info' => 'Third']], $adapter->getMessages());
    }

    public function testReportKeepsEveryMessageWithZeroLimit(): void
    {
        $adapter = new MessageAdapter(0);

        for ($i = 1; $i <= 1001; $i++) {
            $adapter->report('info', 'Message ' . $i);
        }

        $this->assertCount(1001, $adapter->getMessages());
    }

    public function testReportKeepsTheLastThousandMessagesByDefault(): void
    {
        for ($i = 1; $i <= 1001; $i++) {
            $this->adapter->report('info', 'Message ' . $i);
        }

        $messages = $this->adapter->getMessages();

        $this->assertCount(1000, $messages);
        $this->assertSame(['info' => 'Message 2'], $messages[0]);
        $this->assertSame(['info' => 'Message 1001'], $messages[999]);
    }

    public function testReportKeepsOnlyTheLevelAndTheMessageOfAReportWithContext(): void
    {
        $this->adapter->report('error', 'Failed', ['task' => 'cleanup', 'trace' => '#0 main']);

        $this->assertSame([['error' => 'Failed']], $this->adapter->getMessages());
    }
}
