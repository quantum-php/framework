<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Tests\Unit\Mailer\MailerTestCase;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\ResendAdapter;
use Quantum\Config\ConfigSection;

class ResendAdapterTest extends MailerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->adapter = new ResendAdapter(new ConfigSection(['api_key' => 'xxx111222333']));
    }

    public function testResendAdapterInstance(): void
    {
        $this->assertInstanceOf(ResendAdapter::class, $this->adapter);

        $this->assertInstanceOf(MailerInterface::class, $this->adapter);
    }

    public function testResendAdapterSend(): void
    {
        $this->adapter->setFrom('john@hotmail.com', 'John Doe');

        $this->adapter->setAddress('benny@gmail.com', 'Benny');

        $this->adapter->setSubject('Lorem');

        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }
}
