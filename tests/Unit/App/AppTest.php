<?php

namespace Quantum\Tests\Unit\App;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Quantum\App\Adapters\Web\WebAppAdapter;
use Quantum\App\Exceptions\AppException;
use Quantum\App\Contracts\AppInterface;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\App;

#[PreserveGlobalState(false)]
class AppTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->createContext();
    }

    public function tearDown(): void
    {
        $this->removeFrameworkErrorHandlers();
        config()->flush();
        $this->clearAppContext();
    }

    public function testAppGetAdapter(): void
    {
        $adapter = new WebAppAdapter($this->createContext());
        $adapter->boot();

        $app = new App($adapter);

        $this->assertInstanceOf(WebAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());

        config()->flush();

        $adapter = new ConsoleAppAdapter($this->createContext());
        $adapter->boot();

        $app = new App($adapter);

        $this->assertInstanceOf(ConsoleAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());
    }

    public function testAppCallingValidMethod(): void
    {
        $adapter = new WebAppAdapter($this->createContext());
        $adapter->boot();

        $app = new App($adapter);

        request()->create('GET', '/test/am/tests');

        ob_start();
        $this->assertEquals(0, $app->start());
        ob_end_clean();
    }

    public function testAppCallingInvalidMethod(): void
    {
        $adapter = new WebAppAdapter($this->createContext());
        $adapter->boot();

        $app = new App($adapter);

        $this->expectException(AppException::class);

        $this->expectExceptionMessage('The method `callingInvalidMethod` is not supported for `' . WebAppAdapter::class . '`');

        $app->callingInvalidMethod();
    }
}
