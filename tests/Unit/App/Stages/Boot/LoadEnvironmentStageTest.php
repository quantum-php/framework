<?php

namespace Quantum\Tests\Unit\App\Stages\Boot;

use Quantum\App\Stages\Boot\LoadEnvironmentStage;
use Quantum\App\Stages\Boot\LoadHelpersStage;
use Quantum\Tests\Unit\AppTestCase;

class LoadEnvironmentStageTest extends AppTestCase
{
    public function setUp(): void
    {
        $this->context = $this->createContext();

        (new LoadHelpersStage())->process($this->context);
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    public function testLoadEnvironmentStageLoadsEnvVars(): void
    {
        $stage = new LoadEnvironmentStage();
        $stage->process($this->context);

        $this->assertSame('testing', environment()->getAppEnv());
        $this->assertNotEmpty(env('APP_KEY'));
    }
}
