<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Tests\Unit\Mailer\MailerTestCase;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\MailgunAdapter;
use Quantum\Config\ConfigSection;

class MailgunAdapterTest extends MailerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $config = [
            'api_key' => 'xxx11122233',
            'domain' => 'mailgun.example.com',
        ];

        $this->adapter = new MailgunAdapter(new ConfigSection($config));
    }

    public function testMailgunAdapterInstance(): void
    {
        $this->assertInstanceOf(MailgunAdapter::class, $this->adapter);

        $this->assertInstanceOf(MailerInterface::class, $this->adapter);
    }

    public function testMailgunAdapterSend(): void
    {
        $this->adapter->setFrom('john@hotmail.com', 'John Doe');

        $this->adapter->setAddress('benny@gmail.com', 'Benny');

        $this->adapter->setSubject('Lorem');

        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }
}
