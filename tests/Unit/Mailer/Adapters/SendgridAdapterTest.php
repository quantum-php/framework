<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Tests\Unit\Mailer\MailerTestCase;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\SendgridAdapter;
use Quantum\Config\ConfigSection;

class SendgridAdapterTest extends MailerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new SendgridAdapter(new ConfigSection(['api_key' => 'xxx111222333']));
    }

    public function testSendgridAdapterInstance(): void
    {
        $this->assertInstanceOf(SendgridAdapter::class, $this->adapter);

        $this->assertInstanceOf(MailerInterface::class, $this->adapter);
    }

    public function testSendgridAdapterSend(): void
    {
        $this->adapter->setFrom('john@hotmail.com', 'John Doe');

        $this->adapter->setAddress('benny@gmail.com', 'Benny');

        $this->adapter->setSubject('Lorem');

        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }
}
