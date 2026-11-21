<?php

namespace Quantum\Services {

    use Quantum\Service\Service;

    class TestingService extends Service
    {
    }
}

namespace Quantum\Tests\Unit\Service {

    use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
    use PHPUnit\Framework\Attributes\PreserveGlobalState;
    use Quantum\Service\Exceptions\ServiceException;
    use Quantum\Service\Factories\ServiceFactory;
    use Quantum\Services\TestingService;
    use Quantum\Tests\Unit\AppTestCase;
    use Quantum\Service\Service;

    #[RunTestsInSeparateProcesses]
    #[PreserveGlobalState(false)]
    class ServiceTest extends AppTestCase
    {
        public function setUp(): void
        {
            parent::setUp();
        }

        public function testMissingMethods(): void
        {
            $this->expectException(ServiceException::class);

            $this->expectExceptionMessage('The method `undefinedMethod` is not supported for `' . Service::class . '`');

            $service = (new ServiceFactory())->get(TestingService::class);

            $service->undefinedMethod();
        }

    }

}
