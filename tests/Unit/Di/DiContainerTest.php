<?php

namespace Quantum\Tests\Unit\Di;

use Quantum\Service\DummyServiceInterface;
use Quantum\Controllers\TestDiController;
use Quantum\Service\CircularDependencyA;
use Quantum\Service\CircularDependencyB;
use Quantum\View\Factories\ViewFactory;
use Quantum\Di\Exceptions\DiException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Service\DummyService;
use Quantum\Di\DiContainer;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Config\Setup;

class DiContainerTest extends AppTestCase
{
    private DiContainer $container;

    public function setUp(): void
    {
        parent::setUp();
        $this->container = new DiContainer();
    }

    public function testRegisterDependency(): void
    {
        $this->container->register(Setup::class);

        $this->assertInstanceOf(Setup::class, $this->container->get(Setup::class));
    }

    public function testAttemptingToRegisterAlreadyRegisteredDependency(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage('The dependency `Quantum\Config\Setup` is already registered.');

        $this->container->register(Setup::class);
        $this->container->register(Setup::class);
    }

    public function testAttemptingToRegisterNonExistentClass(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage('The dependency `NonExistentClass` is not instantiable.');

        $this->container->register('NonExistentClass');
    }

    public function testAttemptingToRegisterNonExistentAbstract(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage('The dependency `NonExistentInterface` is not valid abstract class.');

        $this->container->register(DummyService::class, 'NonExistentInterface');
    }

    public function testIsRegistered(): void
    {
        $this->assertFalse($this->container->isRegistered(DummyService::class));

        $this->container->register(DummyService::class);

        $this->assertTrue($this->container->isRegistered(DummyService::class));
    }

    public function testHasReturnsFalseBeforeResolve(): void
    {
        $this->container->register(DummyService::class);

        $this->assertFalse($this->container->has(DummyService::class));
    }

    public function testHasReturnsTrueAfterGet(): void
    {
        $this->container->register(DummyService::class);

        $this->container->get(DummyService::class);

        $this->assertTrue($this->container->has(DummyService::class));
    }

    public function testHasReturnsTrueAfterSet(): void
    {
        $instance = new DummyService();

        $this->container->set(DummyServiceInterface::class, $instance);

        $this->assertTrue($this->container->has(DummyServiceInterface::class));
    }

    public function testHasReturnsFalseAfterResetContainer(): void
    {
        $this->container->register(DummyService::class);
        $this->container->get(DummyService::class);

        $this->assertTrue($this->container->has(DummyService::class));

        $this->container->resetContainer();

        $this->assertFalse($this->container->has(DummyService::class));
    }

    public function testHasReturnsFalseAfterReset(): void
    {
        $this->container->register(DummyService::class);
        $this->container->get(DummyService::class);

        $this->assertTrue($this->container->has(DummyService::class));

        $this->container->reset();

        $this->assertFalse($this->container->has(DummyService::class));
    }

    public function testAbstractToConcreteBinding(): void
    {
        $this->container->register(DummyService::class, DummyServiceInterface::class);

        $instance = $this->container->get(DummyServiceInterface::class);

        $this->assertInstanceOf(DummyService::class, $instance);
    }

    public function testSetBindsInstanceToAbstract(): void
    {
        $instance = new DummyService();

        $this->container->set(DummyServiceInterface::class, $instance);

        $resolved = $this->container->get(DummyServiceInterface::class);

        $this->assertSame($instance, $resolved);
        $this->assertInstanceOf(DummyService::class, $resolved);
    }

    public function testSetWorksWithoutPriorRegister(): void
    {
        $instance = new DummyService();

        $this->assertFalse($this->container->isRegistered(DummyServiceInterface::class));

        $this->container->set(DummyServiceInterface::class, $instance);

        $this->assertTrue($this->container->isRegistered(DummyServiceInterface::class));
        $this->assertSame($instance, $this->container->get(DummyServiceInterface::class));
    }

    public function testSetRejectsWrongInstanceType(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage(
            'The dependency `' . DummyServiceInterface::class . '` is not valid abstract class.'
        );

        $this->container->set(DummyServiceInterface::class, new \stdClass());
    }

    public function testSetRejectsInvalidAbstract(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage(
            'The dependency `NonExistentInterface` is not valid abstract class.'
        );

        $this->container->set('NonExistentInterface', new DummyService());
    }

    public function testSetRejectsWhenAlreadyResolved(): void
    {
        $this->container->register(DummyService::class);
        $this->container->get(DummyService::class);

        $this->expectException(DiException::class);
        $this->expectExceptionMessage(
            'The dependency `' . DummyService::class . '` is already registered.'
        );

        $this->container->set(DummyService::class, new DummyService());
    }

    public function testSetOverridesRegisteredButNotResolved(): void
    {
        $this->container->register(DummyService::class, DummyServiceInterface::class);

        $instance = new DummyService();
        $this->container->set(DummyServiceInterface::class, $instance);

        $resolved = $this->container->get(DummyServiceInterface::class);

        $this->assertSame($instance, $resolved);
    }

    public function testGetThrowsForUnregisteredInstantiableClass(): void
    {
        $this->assertFalse($this->container->isRegistered(DiException::class));

        $this->expectException(DiException::class);
        $this->expectExceptionMessage('The dependency `Quantum\Di\Exceptions\DiException` is not registered.');

        $this->container->get(DiException::class);
    }

    public function testGetThrowsForNonInstantiableClass(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage('The dependency `Quantum\Service\DummyServiceInterface` is not registered.');

        $this->container->get(DummyServiceInterface::class);
    }

    public function testCircularDependencyDetectedAtResolve(): void
    {
        $this->expectException(DiException::class);
        $this->expectExceptionMessage(
            'Circular dependency detected: `' . CircularDependencyA::class .
            ' -> ' . CircularDependencyB::class .
            ' -> ' . CircularDependencyA::class . '`'
        );

        $this->container->register(CircularDependencyA::class);
        $this->container->register(CircularDependencyB::class);

        $this->container->create(CircularDependencyA::class);
    }

    public function testGetReturnsSingleton(): void
    {
        $this->container->register(DummyService::class);

        $instance1 = $this->container->get(DummyService::class);
        $instance2 = $this->container->get(DummyService::class);

        $this->assertInstanceOf(DummyService::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testCreateReturnsNewInstance(): void
    {
        $this->container->register(DummyService::class);

        $instance1 = $this->container->create(DummyService::class);
        $instance2 = $this->container->create(DummyService::class);

        $this->assertInstanceOf(DummyService::class, $instance1);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testAutowire(): void
    {
        $this->container->register(Request::class);
        $this->container->register(Response::class);

        $params = $this->container->autowire([new TestDiController(), 'index']);

        $this->assertInstanceOf(Request::class, $params[0]);
        $this->assertInstanceOf(Response::class, $params[1]);
        $this->assertInstanceOf(ViewFactory::class, $params[2]);

        $callback = function (Request $request, Response $response): void {
        };

        $params = $this->container->autowire($callback);

        $this->assertInstanceOf(Request::class, $params[0]);
        $this->assertInstanceOf(Response::class, $params[1]);
    }

    public function testAutowireWithAbstract(): void
    {
        $this->container->register(DummyService::class, DummyServiceInterface::class);

        $controller = new TestDiController();

        $params = $this->container->autowire([$controller, 'handleService']);

        $this->assertInstanceOf(DummyServiceInterface::class, $params[0]);
        $this->assertInstanceOf(DummyService::class, $params[0]);
    }

    public function testAutowireBindsNamedValueByParameterName(): void
    {
        $callback = function (?string $lang, string $uuid): void {
        };

        $params = $this->container->autowire($callback, ['abc'], ['lang' => 'en']);

        $this->assertSame(['en', 'abc'], $params);
    }

    public function testAutowireNamedValueDoesNotDependOnParameterPosition(): void
    {
        $callback = function (string $uuid, ?string $lang): void {
        };

        $params = $this->container->autowire($callback, ['abc'], ['lang' => 'es']);

        $this->assertSame(['abc', 'es'], $params);
    }

    public function testAutowireWithoutNamedValuesBindsPositionally(): void
    {
        $callback = function (?string $lang, string $uuid): void {
        };

        $params = $this->container->autowire($callback, ['en', 'abc']);

        $this->assertSame(['en', 'abc'], $params);
    }

    public function testAutowireIgnoresNamedValueWhenNoParameterHasTheName(): void
    {
        $callback = function (string $uuid): void {
        };

        $params = $this->container->autowire($callback, ['abc'], ['lang' => 'en']);

        $this->assertSame(['abc'], $params);
    }

    public function testAutowireNamedValueDoesNotReplaceClassTypedParameter(): void
    {
        $this->container->register(Request::class);

        $callback = function (Request $request): void {
        };

        $params = $this->container->autowire($callback, [], ['request' => 'text']);

        $this->assertInstanceOf(Request::class, $params[0]);
    }

    public function testResetClearsAll(): void
    {
        $this->container->register(DummyService::class);
        $this->container->get(DummyService::class);

        $this->container->reset();

        $this->assertFalse($this->container->isRegistered(DummyService::class));
        $this->assertFalse($this->container->has(DummyService::class));
    }

    public function testResetContainerKeepsDependencies(): void
    {
        $this->container->register(DummyService::class);
        $this->container->get(DummyService::class);

        $this->container->resetContainer();

        $this->assertTrue($this->container->isRegistered(DummyService::class));
        $this->assertFalse($this->container->has(DummyService::class));
    }

    public function testContainerIsolation(): void
    {
        $container1 = new DiContainer();
        $container1->register(DummyService::class);
        $container1->get(DummyService::class);

        $container2 = new DiContainer();

        $this->assertTrue($container1->isRegistered(DummyService::class));
        $this->assertTrue($container1->has(DummyService::class));

        $this->assertFalse($container2->isRegistered(DummyService::class));
        $this->assertFalse($container2->has(DummyService::class));
    }
}
