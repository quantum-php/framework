<?php

namespace Quantum\Tests\Unit\App;

use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Quantum\App\Exceptions\AppException;
use Quantum\App\Adapters\Web\WebAppAdapter;
use Quantum\App\Contracts\AppInterface;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\App;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class AppTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->createContext();
    }

    public function tearDown(): void
    {
        config()->flush();
        $this->clearAppContext();
    }

    public function testAppGetAdapter(): void
    {
        $app = new App(new WebAppAdapter($this->createContext()));

        $this->assertInstanceOf(WebAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());

        config()->flush();

        $app = new App(new ConsoleAppAdapter($this->createContext()));

        $this->assertInstanceOf(ConsoleAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());
    }

    public function testAppCallingValidMethod(): void
    {
        $app = new App(new WebAppAdapter($this->createContext()));

        request()->create('GET', '/test/am/tests');

        ob_start();
        $this->assertEquals(0, $app->start());
        ob_end_clean();
    }

    public function testAppCallingInvalidMethod(): void
    {
        $app = new App(new WebAppAdapter($this->createContext()));

        $this->expectException(AppException::class);

        $this->expectExceptionMessage('The method `callingInvalidMethod` is not supported for `' . WebAppAdapter::class . '`');

        $app->callingInvalidMethod();
    }
}
