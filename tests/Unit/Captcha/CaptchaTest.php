<?php

namespace Quantum\Tests\Unit\Captcha;

use Quantum\Captcha\Exceptions\CaptchaException;
use Quantum\Captcha\Contracts\CaptchaInterface;
use Quantum\Captcha\Adapters\RecaptchaAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;
use Quantum\Config\ConfigSection;
use Quantum\Captcha\Captcha;

class CaptchaTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCaptchaGetAdapter(): void
    {
        $config = [
            'type' => 'visible',
            'secret_key' => '0xE1a02fB374Bf2',
            'site_key' => '07737dfc-abfa-66ac44365d0c',
        ];

        $captcha = new Captcha(new RecaptchaAdapter(new ConfigSection($config), new HttpClient()));

        $this->assertInstanceOf(RecaptchaAdapter::class, $captcha->getAdapter());

        $this->assertInstanceOf(CaptchaInterface::class, $captcha->getAdapter());
    }

    public function testCaptchaCallingValidMethod(): void
    {
        $config = [
            'type' => 'visible',
            'secret_key' => '0xE1a02fB374Bf2',
            'site_key' => '07737dfc-abfa-66ac44365d0c',
        ];

        $captcha = new Captcha(new RecaptchaAdapter(new ConfigSection($config), new HttpClient()));

        $this->assertEquals('visible', $captcha->getType());
    }

    public function testCacheCallingInvalidMethod(): void
    {
        $config = [
            'type' => 'visible',
            'secret_key' => '0xE1a02fB374Bf2',
            'site_key' => '07737dfc-abfa-66ac44365d0c',
        ];

        $captcha = new Captcha(new RecaptchaAdapter(new ConfigSection($config), new HttpClient()));

        $this->expectException(CaptchaException::class);

        $this->expectExceptionMessage('The method `callingInvalidMethod` is not supported for `' . RecaptchaAdapter::class . '`');

        $captcha->callingInvalidMethod();
    }
}
