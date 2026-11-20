<?php

namespace Quantum\Services {

    use Quantum\Service\Service;
    use Quantum\Models\TestModel;

    class TestService extends Service
    {
        public static $count = 0;

        /**
         * @var TestModel
         */
        public $model;

        public function __construct(TestModel $model)
        {
            self::$count++;

            $this->model = $model;
        }

        public function hello(): string
        {
            return 'Hello';
        }
    }
}

namespace Quantum\Models {

    use Quantum\Model\DbModel;

    class TestModel extends DbModel
    {
    }
}

namespace Quantum\Tests\Unit\Service\Factories {

    use Quantum\Service\Exceptions\ServiceException;
    use Quantum\Service\Factories\ServiceFactory;
    use Quantum\Tests\Unit\AppTestCase;
    use Quantum\Services\TestService;
    use Quantum\Service\Service;
    use Quantum\Model\DbModel;

    class ServiceFactoryTest extends AppTestCase
    {
        public function setUp(): void
        {
            parent::setUp();
        }

        public function tearDown(): void
        {
            TestService::$count = 0;

            parent::tearDown();
        }

        public function testServiceFactoryGetInstance(): void
        {
            $service = ServiceFactory::get(TestService::class);

            $this->assertInstanceOf(Service::class, $service);

            $this->assertInstanceOf(TestService::class, $service);
        }

        public function testServiceFactoryVerifySingletonInstance(): void
        {
            $testServiceOne = ServiceFactory::get(TestService::class);

            $this->assertEquals(1, TestService::$count);

            $testServiceTwo = ServiceFactory::get(TestService::class);

            $this->assertEquals(1, TestService::$count);

            $this->assertSame($testServiceOne, $testServiceTwo);
        }

        public function testServiceFactoryCreateInstance(): void
        {
            $service = ServiceFactory::create(TestService::class);

            $this->assertInstanceOf(Service::class, $service);

            $this->assertInstanceOf(TestService::class, $service);
        }

        public function testServiceFactoryVerifyFreshInstance(): void
        {
            $testServiceOne = ServiceFactory::create(TestService::class);

            $this->assertEquals(1, TestService::$count);

            $testServiceTwo = ServiceFactory::create(TestService::class);

            $this->assertEquals(2, TestService::$count);

            $this->assertNotSame($testServiceOne, $testServiceTwo);
        }

        public function testServiceFactoryVerifyConstructorDependencyResolved(): void
        {
            $testService = ServiceFactory::get(TestService::class);

            $this->assertInstanceOf(DbModel::class, $testService->model);
        }

        public function testServiceFactoryServiceMethodCall(): void
        {
            $this->assertEquals('Hello', ServiceFactory::get(TestService::class)->hello());

            $this->assertEquals('Hello', ServiceFactory::create(TestService::class)->hello());
        }

        public function testServiceFactoryServiceNotFound(): void
        {
            $this->expectException(ServiceException::class);

            $this->expectExceptionMessage('Service `NonExistentClass` not found');

            ServiceFactory::get(\NonExistentClass::class);
        }
    }
}
