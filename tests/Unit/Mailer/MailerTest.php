<?php

namespace Quantum\Tests\Unit\Mailer;

use Quantum\Mailer\Adapters\SendinblueAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use Quantum\Mailer\Adapters\MandrillAdapter;
use Quantum\Mailer\Adapters\SendgridAdapter;
use Quantum\Mailer\Adapters\MailgunAdapter;
use Quantum\Mailer\Adapters\ResendAdapter;
use Quantum\Mailer\Exceptions\MailerException;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\SmtpAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Mailer\Mailer;
use Quantum\Config\Setup;
use ReflectionMethod;

class MailerTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }
    }

    public function testMailerGetAdapter(): void
    {
        $mailer = new Mailer(new SmtpAdapter(config()->get('mailer.smtp')));

        $this->assertInstanceOf(SmtpAdapter::class, $mailer->getAdapter());

        $this->assertInstanceOf(MailerInterface::class, $mailer->getAdapter());
    }

    public function testMailerCallingValidMethod(): void
    {
        $mailer = new Mailer(new SmtpAdapter(config()->get('mailer.smtp')));

        $mailer->setSubject('Welcome');

        $this->assertEquals('Welcome', $mailer->getSubject());
    }

    public function testMailerCallingInvalidMethod(): void
    {
        $mailer = new Mailer(new SmtpAdapter(config()->get('mailer.smtp')));

        $this->expectException(MailerException::class);

        $this->expectExceptionMessage('The method `callingInvalidMethod` is not supported for `' . SmtpAdapter::class . '`');

        $mailer->callingInvalidMethod();
    }

    #[DataProvider('httpAdapterProvider')]
    public function testHttpAdapterGetTransportErrorsReturnsArray(string $adapterClass, array $params): void
    {
        $adapter = new $adapterClass($params);

        $httpClient = $this->getPrivateProperty($adapter, 'httpClient');
        $httpClient->createRequest('http://localhost');

        $method = new ReflectionMethod($adapter, 'getTransportErrors');
        $method->setAccessible(true);

        $errors = $method->invoke($adapter);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    /**
     * @return array<string, array{0: class-string, 1: array<string, mixed>}>
     */
    public static function httpAdapterProvider(): array
    {
        return [
            'mailgun' => [MailgunAdapter::class, ['api_key' => 'test', 'domain' => 'test.com']],
            'mandrill' => [MandrillAdapter::class, ['api_key' => 'test']],
            'resend' => [ResendAdapter::class, ['api_key' => 'test']],
            'sendgrid' => [SendgridAdapter::class, ['api_key' => 'test']],
            'sendinblue' => [SendinblueAdapter::class, ['api_key' => 'test']],
        ];
    }
}
