<?php

namespace Quantum\Tests\Unit\Config\Exceptions;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Tests\Unit\AppTestCase;

class ConfigExceptionTest extends AppTestCase
{
    public function testConfigCollision(): void
    {
        $exception = ConfigException::configCollision('database');

        $this->assertInstanceOf(ConfigException::class, $exception);
        $this->assertSame('Config key `database` is already in use', $exception->getMessage());
        $this->assertSame(E_WARNING, $exception->getCode());
    }

    public function testInvalidValue(): void
    {
        $exception = ConfigException::invalidValue('lang.url_segment', 'abc');

        $this->assertInstanceOf(ConfigException::class, $exception);
        $this->assertSame('The config key `lang.url_segment` has the value `abc`, which is not a whole number.', $exception->getMessage());
        $this->assertSame(E_ERROR, $exception->getCode());
    }
}
