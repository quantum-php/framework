<?php

namespace Quantum\Tests\Unit\Captcha\Adapters;

use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\Captcha\Adapters\RecaptchaAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;
use Exception;
use Mockery;

class RecaptchaAdapterTest extends AppTestCase
{
    public $httpClientMock;
    private string $secretKey = '10000000-ffff-ffff-ffff-000000000001';
    private string $siteKey = '0x0000000000000000000000000000000000000000';

    private RecaptchaAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('captcha.recaptcha.secret_key', $this->secretKey);
        config()->set('captcha.recaptcha.site_key', $this->siteKey);

        $this->httpClientMock = Mockery::mock(HttpClient::class);

        $this->adapter = new RecaptchaAdapter(config()->section('captcha.recaptcha'), $this->httpClientMock);
    }

    public function tearDown(): void
    {
        asset()->flush();
        Mockery::close();

        parent::tearDown();
    }

    public function testRecaptchaAdapterInstance(): void
    {
        $this->assertInstanceOf(CaptchaInterface::class, $this->adapter);

        $this->assertInstanceOf(RecaptchaAdapter::class, $this->adapter);
    }

    public function testRecaptchaSetGetType(): void
    {
        $this->assertNull($this->adapter->getType());

        $this->adapter->setType(CaptchaInterface::CAPTCHA_VISIBLE);

        $this->assertEquals(CaptchaInterface::CAPTCHA_VISIBLE, $this->adapter->getType());

        $this->adapter->setType(CaptchaInterface::CAPTCHA_INVISIBLE);

        $this->assertEquals(CaptchaInterface::CAPTCHA_INVISIBLE, $this->adapter->getType());

        $this->expectException(Exception::class);

        $this->expectExceptionMessage('Provided captcha type is not valid');

        $this->adapter->setType('test');
    }

    public function testRecaptchaVisibleAddToForm(): void
    {
        $this->adapter->setType(CaptchaInterface::CAPTCHA_VISIBLE);

        $this->assertEquals(
            '<div class="g-recaptcha" data-sitekey="' . $this->siteKey . '" ></div>',
            $this->adapter->addToForm('signUpForm')
        );
    }

    public function testRecaptchaInvisibleAddToForm(): void
    {
        $this->adapter->setType(CaptchaInterface::CAPTCHA_INVISIBLE);

        $formId = 'signUpForm';

        $actual = $this->adapter->addToForm($formId);

        $expected = '<script>
                 document.addEventListener("DOMContentLoaded", function() {
                     const form = document.getElementById("signUpForm");
                     const submitButton = form.querySelector("button[type=submit]");
                     submitButton.setAttribute("data-sitekey", "0x0000000000000000000000000000000000000000");
                     submitButton.setAttribute("data-callback", "onSubmit");
                     submitButton.classList.add("g-recaptcha");
                 })
                function onSubmit (){
                    document.getElementById("signUpForm").submit();
                }
            </script>';

        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertSame($expected, $actual);
    }

    public function testRecaptchaVerifySuccess(): void
    {

        $this->httpClientMock
            ->shouldReceive('createRequest')
            ->with(Mockery::type('string'))
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('setMethod')
            ->with('GET')
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('start')
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('getResponseBody')
            ->andReturn((object) [
                'success' => true,
            ]);

        $result = $this->adapter->verify('valid_code');

        $this->assertTrue($result);
    }

    public function testRecaptchaVerifyFailure(): void
    {
        $this->httpClientMock
            ->shouldReceive('createRequest')
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('setMethod')
            ->with('GET')
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('start')
            ->andReturnSelf();

        $this->httpClientMock
            ->shouldReceive('getResponseBody')
            ->andReturn((object) [
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]);

        $result = $this->adapter->verify('invalid_code');

        $this->assertFalse($result);

        $this->assertEquals('invalid-input-response', $this->adapter->getErrorMessage());
    }
}
