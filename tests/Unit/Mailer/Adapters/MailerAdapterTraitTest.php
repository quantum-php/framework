<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Mailer\Adapters\SendinblueAdapter;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\SmtpAdapter;
use Quantum\Mailer\Traits\MailerTrait;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;
use Quantum\Config\Setup;

class MailerAdapterTraitTest extends AppTestCase
{
    private SmtpAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }

        $this->adapter = new SmtpAdapter(config()->section('mailer.smtp'));
    }

    public function testMailerTraitSetGetFrom(): void
    {
        $this->adapter->setFrom('johndoe@gmail.com', 'John Doe');

        $from = $this->adapter->getFrom();

        $this->assertIsArray($from);

        $this->assertEquals('johndoe@gmail.com', $from['email']);

        $this->assertEquals('John Doe', $from['name']);
    }

    public function testMailerTraitSetGetAddresses(): void
    {
        $this->adapter->setAddress('johndoe@gmail.com', 'John Doe');

        $addresses = $this->adapter->getAddresses();

        $this->assertIsArray($addresses);

        $this->assertIsArray($addresses[0]);

        $this->assertEquals('johndoe@gmail.com', $addresses[0]['email']);

        $this->assertEquals('John Doe', $addresses[0]['name']);
    }

    public function testMailerTraitSetGetSubject(): void
    {
        $this->adapter->setSubject('Lorem ipsum');

        $this->assertIsString($this->adapter->getSubject());

        $this->assertEquals('Lorem ipsum', $this->adapter->getSubject());
    }

    public function testMailerTraitSetGetTemplate(): void
    {

        $templatePath = base_dir() . DS . 'shared' . DS . 'views' . DS . 'email' . DS . 'template';

        $this->adapter->setTemplate($templatePath);

        $this->assertIsString($this->adapter->getTemplate());

        $this->assertEquals($templatePath, $this->adapter->getTemplate());
    }

    public function testSetGetBody(): void
    {
        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertIsString($this->adapter->getBody());

        $this->assertEquals('Lorem ipsum dolor sit amet', $this->adapter->getBody());

        $this->adapter->setBody(['Lorem ipsum', 'dolor sit amet']);

        $this->assertIsArray($this->adapter->getBody());

        $this->assertEquals('Lorem ipsum', $this->adapter->getBody()[0]);
    }

    public function testMailerTraitGetMessageId(): void
    {
        $adapter = new SendinblueAdapter(new ConfigSection(['api_key' => 'xxx11122233']));

        $this->assertIsString($adapter->getMessageId());
    }

    public function testMailerTraitGetTransportLogIsEmptyBeforeASend(): void
    {
        $this->assertSame([], $this->adapter->getTransportLog());
    }

    public function testMailerTraitSendKeepsTheTransportErrorsOfAFailedSendAndLogsThem(): void
    {
        config()->set('app.debug', true);
        config()->set('mailer.mail_trap', false);

        $adapter = new class () implements MailerInterface {
            use MailerTrait;

            protected function prepare(): void
            {
            }

            protected function sendEmail(): bool
            {
                return false;
            }

            protected function getTransportErrors(): array
            {
                return ['Connection refused'];
            }
        };

        $this->assertFalse($adapter->send());

        $this->assertSame(['Connection refused'], $adapter->getTransportLog());
        $this->assertSame([['warning' => 'Connection refused']], logger()->getAdapter()->getMessages());
    }

    public function testMailerTraitSendCutsTheTransportLogToItsLastLinesBeforeSending(): void
    {
        config()->set('mailer.transport_log_limit', 2);

        $adapter = $this->createFailingAdapter(1);

        for ($i = 1; $i <= 4; $i++) {
            $adapter->send();
        }

        $this->assertSame(['Send 2 line 1', 'Send 3 line 1', 'Send 4 line 1'], $adapter->getTransportLog());
    }

    public function testMailerTraitSendKeepsEveryLineOfTheSendInProgress(): void
    {
        config()->set('mailer.transport_log_limit', 1);

        $adapter = $this->createFailingAdapter(3);

        $adapter->send();

        $this->assertSame(['Send 1 line 1', 'Send 1 line 2', 'Send 1 line 3'], $adapter->getTransportLog());

        $adapter->send();

        $this->assertSame(
            ['Send 1 line 3', 'Send 2 line 1', 'Send 2 line 2', 'Send 2 line 3'],
            $adapter->getTransportLog()
        );
    }

    public function testMailerTraitSendKeepsTheWholeTransportLogWithZeroLimit(): void
    {
        config()->set('mailer.transport_log_limit', 0);

        $adapter = $this->createFailingAdapter(1);

        for ($i = 1; $i <= 5; $i++) {
            $adapter->send();
        }

        $this->assertCount(5, $adapter->getTransportLog());
    }

    public function testMailerTraitSendKeepsTheLastThousandLinesWithoutTheKey(): void
    {
        config()->delete('mailer.transport_log_limit');

        $adapter = $this->createFailingAdapter(1001);

        $adapter->send();
        $adapter->send();

        $log = $adapter->getTransportLog();

        $this->assertCount(2001, $log);
        $this->assertSame('Send 1 line 2', $log[0]);
    }

    /**
     * Creates an adapter whose every send fails with the given number of transport error lines
     */
    private function createFailingAdapter(int $linesPerSend): MailerInterface
    {
        config()->set('mailer.mail_trap', false);

        return new class ($linesPerSend) implements MailerInterface {
            use MailerTrait;

            private int $linesPerSend;

            private int $sends = 0;

            public function __construct(int $linesPerSend)
            {
                $this->linesPerSend = $linesPerSend;
            }

            protected function prepare(): void
            {
            }

            protected function sendEmail(): bool
            {
                $this->sends++;

                return false;
            }

            protected function getTransportErrors(): array
            {
                $lines = [];

                for ($i = 1; $i <= $this->linesPerSend; $i++) {
                    $lines[] = 'Send ' . $this->sends . ' line ' . $i;
                }

                return $lines;
            }
        };
    }
}
