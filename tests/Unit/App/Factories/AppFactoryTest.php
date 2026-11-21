<?php

namespace Quantum\Tests\Unit\App\Factories;

use Quantum\App\Adapters\Console\ConsoleAppAdapter;
use Quantum\App\Adapters\Web\WebAppAdapter;
use Quantum\App\Exceptions\AppException;
use Quantum\App\Contracts\AppInterface;
use Quantum\App\Factories\AppFactory;
use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;
use PHPUnit\Framework\TestCase;
use Quantum\App\Enums\AppType;
use Quantum\App\App;
use Quantum\Di\Di;

class AppFactoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testAppFactoryInstance(): void
    {
        $app = AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertInstanceOf(App::class, $app);
    }

    public function tearDown(): void
    {
        config()->flush();
        AppTestCase::removeFrameworkErrorHandlers();
    }

    public function testAppFactoryConsoleAdapter(): void
    {
        $app = AppFactory::create(AppType::CONSOLE, PROJECT_ROOT);

        $this->assertInstanceOf(ConsoleAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());
    }

    public function testAppFactoryWebAdapter(): void
    {
        $app = AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertInstanceOf(WebAppAdapter::class, $app->getAdapter());

        $this->assertInstanceOf(AppInterface::class, $app->getAdapter());
    }

    public function testAppFactoryBootsTheCreatedApp(): void
    {
        AppFactory::destroy(AppType::WEB);

        AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertTrue(Di::has(Environment::class));
        $this->assertTrue(config()->has('app'));
    }

    public function testAppFactoryInvalidAdapter(): void
    {
        $this->expectException(AppException::class);

        $this->expectExceptionMessage('The adapter `invalid_type` is not supported');

        AppFactory::create('invalid_type', PROJECT_ROOT);
    }

    public function testAppFactoryReturnsSameInstance(): void
    {
        $app1 = AppFactory::create(AppType::WEB, PROJECT_ROOT);
        $app2 = AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertSame($app1, $app2);
    }

    public function testAppFactoryDestroy(): void
    {
        $app1 = AppFactory::create(AppType::WEB, PROJECT_ROOT);

        AppFactory::destroy(AppType::WEB);

        $app2 = AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertInstanceOf(App::class, $app2);

        $this->assertNotSame($app1, $app2);
    }

    public function testAppFactorySetsTheContextTypeOfTheCreatedApp(): void
    {
        AppFactory::destroy(AppType::WEB);
        AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertSame(AppType::WEB, App::getContext()->getType());

        AppFactory::destroy(AppType::CONSOLE);
        AppFactory::create(AppType::CONSOLE, PROJECT_ROOT);

        $this->assertSame(AppType::CONSOLE, App::getContext()->getType());
    }

    public function testAppFactoryResetsContainerOnCreate(): void
    {
        AppFactory::destroy(AppType::WEB);

        Di::register(\stdClass::class);

        $this->assertTrue(Di::isRegistered(\stdClass::class));

        AppFactory::create(AppType::WEB, PROJECT_ROOT);

        $this->assertFalse(Di::isRegistered(\stdClass::class));
    }
}
