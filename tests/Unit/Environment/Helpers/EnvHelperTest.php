<?php

namespace Quantum\Tests\Unit\Environment\Helpers;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;

class EnvHelperTest extends AppTestCase
{
    public function testEnvironmentHelperReturnsInstance(): void
    {
        $this->assertInstanceOf(Environment::class, environment());
    }

    public function testEnvironmentHelperReturnsSameInstance(): void
    {
        $this->assertSame(environment(), environment());
    }

    public function testGetEnvValue(): void
    {
        $this->assertNotNull(env('APP_KEY'));

        $this->assertNotNull(env('DEBUG'));

        $this->assertEquals('TRUE', env('DEBUG'));
    }

    public function testGetEnvValueWithDefault(): void
    {
        $this->assertNull(env('NON_EXISTING'));
        $this->assertEquals('fallback', env('NON_EXISTING', 'fallback'));
    }

    public function testEnvBoolConvertsTextToBoolean(): void
    {
        $this->setEnvValues(['FLAG_FALSE' => 'false', 'FLAG_ZERO' => '0', 'FLAG_EMPTY' => '', 'FLAG_TRUE' => 'true']);

        $this->assertTrue(env_bool('DEBUG'));
        $this->assertTrue(env_bool('FLAG_TRUE'));
        $this->assertFalse(env_bool('FLAG_FALSE', true));
        $this->assertFalse(env_bool('FLAG_ZERO', true));
        $this->assertFalse(env_bool('FLAG_EMPTY', true));
    }

    public function testEnvBoolUsesDefaultForMissingKey(): void
    {
        $this->assertFalse(env_bool('NON_EXISTING'));
        $this->assertTrue(env_bool('NON_EXISTING', true));
    }

    public function testEnvIntConvertsTextToInteger(): void
    {
        $this->setEnvValues(['MAIL_PORT' => '587']);

        $this->assertSame(587, env_int('MAIL_PORT'));
    }

    public function testEnvIntUsesDefaultForMissingKey(): void
    {
        $this->assertSame(0, env_int('NON_EXISTING'));
        $this->assertSame(25, env_int('NON_EXISTING', 25));
    }

    public function testEnvIntThrowsForInvalidNumber(): void
    {
        $this->setEnvValues(['MAIL_PORT' => 'abc']);

        $this->expectException(EnvException::class);

        env_int('MAIL_PORT');
    }

    /**
     * @param array<string, string> $values
     */
    private function setEnvValues(array $values): void
    {
        $content = $this->getPrivateProperty(environment(), 'envContent');

        $this->setPrivateProperty(environment(), 'envContent', array_merge($content, $values));
    }
}
