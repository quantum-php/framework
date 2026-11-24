<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Mailer\Adapters\SendinblueAdapter;
use Quantum\Tests\Unit\Mailer\MailerTestCase;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Config\ConfigSection;

class SendinblueAdapterTest extends MailerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new SendinblueAdapter(new ConfigSection(['api_key' => 'xxx111222333']));
    }

    public function testSendinblueAdapterInstance(): void
    {
        $this->assertInstanceOf(SendinblueAdapter::class, $this->adapter);

        $this->assertInstanceOf(MailerInterface::class, $this->adapter);
    }

    public function testSendinblueAdapterSend(): void
    {
        $this->adapter->setFrom('john@hotmail.com', 'John Doe');

        $this->adapter->setAddress('benny@gmail.com', 'Benny');

        $this->adapter->setSubject('Lorem');

        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }
}
