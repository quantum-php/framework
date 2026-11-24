<?php

namespace Quantum\Tests\Unit\Captcha\Factories;

use Quantum\Captcha\Exceptions\CaptchaException;
use Quantum\Captcha\Adapters\RecaptchaAdapter;
use Quantum\Captcha\Adapters\HcaptchaAdapter;
use Quantum\Captcha\Factories\CaptchaFactory;
use Quantum\Captcha\Enums\CaptchaType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Captcha\Captcha;
use Quantum\Config\Setup;
use Quantum\Di\Di;

class CaptchaFactoryTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->resetCaptchaFactory();
    }

    public function testCaptchaFactoryInstance(): void
    {
        $captcha = CaptchaFactory::get();

        $this->assertInstanceOf(Captcha::class, $captcha);
    }

    public function testCacheFactoryDefaultAdapter(): void
    {
        $captcha = CaptchaFactory::get();

        $this->assertInstanceOf(RecaptchaAdapter::class, $captcha->getAdapter());
    }

    public function testCacheFactoryRecaptchaAdapter(): void
    {
        $captcha = CaptchaFactory::get(CaptchaType::RECAPTCHA);

        $this->assertInstanceOf(RecaptchaAdapter::class, $captcha->getAdapter());
    }

    public function testCacheFactoryHcaptchaAdapter(): void
    {
        $captcha = CaptchaFactory::get(CaptchaType::HCAPTCHA);

        $this->assertInstanceOf(HcaptchaAdapter::class, $captcha->getAdapter());
    }

    public function testCacheFactoryInvalidTypeAdapter(): void
    {
        $this->expectException(CaptchaException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        CaptchaFactory::get('invalid_type');
    }

    public function testAuthFactoryReturnsSameInstance(): void
    {
        $captcha1 = CaptchaFactory::get(CaptchaType::RECAPTCHA);
        $captcha2 = CaptchaFactory::get(CaptchaType::RECAPTCHA);

        $this->assertSame($captcha1, $captcha2);
    }

    public function testCaptchaFactoryKeepsAMissingTypeAsNull(): void
    {
        if (!config()->has('captcha')) {
            config()->import(new Setup('config', 'captcha'));
        }

        config()->set('captcha.recaptcha', ['secret_key' => 'secret', 'site_key' => 'site']);

        $captcha = CaptchaFactory::get(CaptchaType::RECAPTCHA);

        $this->assertNull($captcha->getAdapter()->getType());
    }

    private function resetCaptchaFactory(): void
    {
        if (!Di::isRegistered(CaptchaFactory::class)) {
            Di::register(CaptchaFactory::class);
        }

        $factory = Di::get(CaptchaFactory::class);
        $this->setPrivateProperty($factory, 'instances', []);
    }
}
