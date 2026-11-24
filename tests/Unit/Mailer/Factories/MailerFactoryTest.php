<?php

namespace Quantum\Tests\Unit\Mailer\Factories;

use Quantum\Mailer\Exceptions\MailerException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Mailer\Adapters\SendinblueAdapter;
use Quantum\Mailer\Adapters\MandrillAdapter;
use Quantum\Mailer\Adapters\SendgridAdapter;
use Quantum\Mailer\Adapters\MailgunAdapter;
use Quantum\Mailer\Adapters\ResendAdapter;
use Quantum\Mailer\Factories\MailerFactory;
use Quantum\Mailer\Adapters\SmtpAdapter;
use Quantum\Mailer\Enums\MailerType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Mailer\Mailer;
use Quantum\Config\Setup;
use Quantum\Di\Di;

class MailerFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetMailerFactory();

        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }
    }

    public function testMailerFactoryInstance(): void
    {
        $mailer = MailerFactory::get();

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testMailerFactoryGetDefaultAdapter(): void
    {
        $mailer = MailerFactory::get();

        $this->assertInstanceOf(SmtpAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetSmtpAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::SMTP);

        $this->assertInstanceOf(SmtpAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetMailgunAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::MAILGUN);

        $this->assertInstanceOf(MailgunAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetMandrillAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::MANDRILL);

        $this->assertInstanceOf(MandrillAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetSendgridAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::SENDGRID);

        $this->assertInstanceOf(SendgridAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetSendinblueAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::SENDINBLUE);

        $this->assertInstanceOf(SendinblueAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetResendAdapter(): void
    {
        $mailer = MailerFactory::get(MailerType::RESEND);

        $this->assertInstanceOf(ResendAdapter::class, $mailer->getAdapter());
    }

    public function testMailerFactoryGetInvalidTypeAdapter(): void
    {
        $this->expectException(MailerException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        MailerFactory::get('invalid_type');
    }

    public function testMailerFactoryReturnsSameInstance(): void
    {
        $mailer1 = MailerFactory::get(MailerType::SMTP);
        $mailer2 = MailerFactory::get(MailerType::SMTP);

        $this->assertSame($mailer1, $mailer2);
    }

    public function testMailerFactoryReadsTheSmtpPortGivenAsText(): void
    {
        config()->set('mailer.smtp.port', '587');

        $adapter = MailerFactory::get(MailerType::SMTP)->getAdapter();

        $this->assertSame(587, $this->getPrivateProperty($adapter, 'mailer')->Port);
    }

    public function testMailerFactoryRejectsAnSmtpPortThatIsNotANumber(): void
    {
        config()->set('mailer.smtp.port', 'abc');

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The config key `port` has the value `abc`, which is not a whole number.');

        MailerFactory::get(MailerType::SMTP);
    }

    private function resetMailerFactory(): void
    {
        if (!Di::isRegistered(MailerFactory::class)) {
            Di::register(MailerFactory::class);
        }

        $factory = Di::get(MailerFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
