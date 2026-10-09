<?php

namespace Quantum\Tests\Unit\App\Stages\Boot;

use Quantum\App\Stages\Boot\SetupErrorHandlerStage;
use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadAppConfigStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\Tests\Unit\AppTestCase;

class SetupErrorHandlerStageTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext();

        (new LoadHelpersStage())->process($this->context);
        (new LoadEnvironmentStage())->process($this->context);
        (new LoadAppConfigStage())->process($this->context);
    }

    public function tearDown(): void
    {
        restore_error_handler();
        restore_exception_handler();
        config()->flush();
        $this->clearAppContext();
    }

    public function testSetupErrorHandlerStageRegistersHandlers(): void
    {
        $stage = new SetupErrorHandlerStage();
        $stage->process($this->context);

        $errorHandler = set_error_handler(function () {
        });
        restore_error_handler();

        $this->assertNotNull($errorHandler);
    }
}
