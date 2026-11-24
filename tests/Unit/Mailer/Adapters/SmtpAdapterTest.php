<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Tests\Unit\Mailer\MailerTestCase;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\SmtpAdapter;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Quantum\Config\Setup;

class SmtpAdapterTest extends MailerTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }

        $this->adapter = new SmtpAdapter(config()->section('mailer.smtp'));
    }

    public function testSmtpAdapterInstance(): void
    {
        $this->assertInstanceOf(SmtpAdapter::class, $this->adapter);

        $this->assertInstanceOf(MailerInterface::class, $this->adapter);
    }

    public function testSmtpAdapterSetGetReplays(): void
    {
        $this->adapter->setReplay('jonny@hotmail.com', 'Jonny')
            ->setReplay('jane@gmail.com');

        $replays = $this->adapter->getReplays();

        $this->assertIsArray($replays);

        $this->assertEquals('jonny@hotmail.com', $replays[0]['email']);

        $this->assertEquals('Jonny', $replays[0]['name']);

        $this->assertEquals('jane@gmail.com', $replays[1]['email']);

        $this->assertNull($replays[1]['name']);
    }

    public function testSmtpAdapterSetGetCCs(): void
    {
        $this->adapter->setCC('jonny@hotmail.com', 'Jonny')->setCC('jane@gmail.com');

        $CCs = $this->adapter->getCCs();

        $this->assertIsArray($CCs);

        $this->assertIsArray($CCs[0]);

        $this->assertEquals('jonny@hotmail.com', $CCs[0]['email']);

        $this->assertEquals('Jonny', $CCs[0]['name']);

    }

    public function testSmtpAdapterSetGetBCCs(): void
    {
        $this->adapter->setBCC('jonny@hotmail.com', 'Jonny')->setBCC('jane@gmail.com');

        $BCCs = $this->adapter->getBCCs();

        $this->assertIsArray($BCCs);

        $this->assertIsArray($BCCs[0]);

        $this->assertEquals('jonny@hotmail.com', $BCCs[0]['email']);

        $this->assertEquals('Jonny', $BCCs[0]['name']);
    }

    public function testSmtpAdapterSetGetAttachments(): void
    {
        $this->adapter->setAttachment(base_dir() . DS . 'php8fe1.tmp');

        $attachments = $this->adapter->getAttachments();

        $this->assertIsArray($attachments);

        $this->assertEquals(base_dir() . DS . 'php8fe1.tmp', current($attachments));
    }

    public function testSmtpAdapterSetGetStringAttachments(): void
    {
        $this->adapter->setStringAttachment('content of the document', 'document.txt');

        $attachments = $this->adapter->getStringAttachments();

        $this->assertIsArray($attachments);

        $this->assertEquals('content of the document', current($attachments)['content']);

        $this->assertEquals('document.txt', current($attachments)['filename']);
    }

    public function testSmtpAdapterKeepsTheSmtpExchangeInDebugMode(): void
    {
        config()->set('app.debug', true);

        $adapter = new SmtpAdapter(config()->section('mailer.smtp'));

        $debugOutput = $this->getPrivateProperty($adapter, 'mailer')->Debugoutput;

        $debugOutput('CLIENT -> SERVER: EHLO localhost', 2);

        $this->assertSame(['CLIENT -> SERVER: EHLO localhost'], $adapter->getTransportLog());
    }

    public function testSmtpAdapterCutsTheSmtpExchangeToItsLastLinesAtTheNextSend(): void
    {
        config()->set('app.debug', true);
        config()->set('mailer.transport_log_limit', 2);

        $adapter = new SmtpAdapter(config()->section('mailer.smtp'));

        $debugOutput = $this->getPrivateProperty($adapter, 'mailer')->Debugoutput;

        $debugOutput('CLIENT -> SERVER: EHLO localhost', 2);
        $debugOutput('CLIENT -> SERVER: MAIL FROM:<john@hotmail.com>', 2);
        $debugOutput('CLIENT -> SERVER: QUIT', 2);

        $adapter->setFrom('john@hotmail.com', 'John Doe');
        $adapter->setAddress('benny@gmail.com', 'Benny');
        $adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($adapter->send());

        $this->assertSame(
            ['CLIENT -> SERVER: MAIL FROM:<john@hotmail.com>', 'CLIENT -> SERVER: QUIT'],
            $adapter->getTransportLog()
        );
    }

    public function testSmtpAdapterKeepsTheSmtpExchangeOfAFailedSendAndCutsItAtTheNextSend(): void
    {
        config()->set('app.debug', true);
        config()->set('mailer.mail_trap', false);
        config()->set('mailer.transport_log_limit', 1);

        $adapter = new SmtpAdapter(config()->section('mailer.smtp'));

        $failingMailer = new class (true) extends PHPMailer {
            public int $sends = 0;

            public function send()
            {
                $this->sends++;

                call_user_func($this->Debugoutput, 'Send ' . $this->sends . ': CLIENT -> SERVER: EHLO localhost', 2);
                call_user_func($this->Debugoutput, 'Send ' . $this->sends . ': SERVER -> CLIENT: 554 Rejected', 2);

                throw new Exception('SMTP Error: data not accepted.');
            }
        };

        $failingMailer->Debugoutput = $this->getPrivateProperty($adapter, 'mailer')->Debugoutput;
        $this->setPrivateProperty($adapter, 'mailer', $failingMailer);

        for ($i = 1; $i <= 2; $i++) {
            $adapter->setFrom('john@hotmail.com', 'John Doe');
            $adapter->setAddress('benny@gmail.com', 'Benny');

            try {
                $adapter->send();
                $this->fail('Expected the SMTP send to throw');
            } catch (Exception $e) {
                $this->assertSame('SMTP Error: data not accepted.', $e->getMessage());
            }
        }

        $this->assertSame([
            'Send 1: SERVER -> CLIENT: 554 Rejected',
            'Send 2: CLIENT -> SERVER: EHLO localhost',
            'Send 2: SERVER -> CLIENT: 554 Rejected',
        ], $adapter->getTransportLog());
    }

    public function testSmtpAdapterSendsThroughTheTransportWhenMailTrapIsTheTextFalse(): void
    {
        config()->set('mailer.mail_trap', 'false');

        $adapter = new SmtpAdapter(config()->section('mailer.smtp'));

        $this->setPrivateProperty($adapter, 'mailer', new class (true) extends PHPMailer {
            public function send()
            {
                throw new Exception('Sent through the transport');
            }
        });

        $adapter->setFrom('john@hotmail.com', 'John Doe');
        $adapter->setAddress('benny@gmail.com', 'Benny');
        $adapter->setBody('Lorem ipsum dolor sit amet');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sent through the transport');

        $adapter->send();
    }

    public function testSmtpAdapterSavesToTheMailTrapWhenMailTrapIsTheTextTrue(): void
    {
        config()->set('mailer.mail_trap', 'true');

        $this->adapter->setFrom('john@hotmail.com', 'John Doe');
        $this->adapter->setAddress('benny@gmail.com', 'Benny');
        $this->adapter->setSubject('Lorem');
        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }

    public function testSmtpAdapterKeepsNoSmtpExchangeOutsideDebugMode(): void
    {
        config()->set('app.debug', false);

        $adapter = new SmtpAdapter(config()->section('mailer.smtp'));

        $this->assertSame(SMTP::DEBUG_OFF, $this->getPrivateProperty($adapter, 'mailer')->SMTPDebug);
    }

    public function testSmtpAdapterSend(): void
    {
        $this->adapter->setFrom('john@hotmail.com', 'John Doe');

        $this->adapter->setAddress('benny@gmail.com', 'Benny');

        $this->adapter->setSubject('Lorem');

        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertTrue($this->adapter->send());
    }
}
