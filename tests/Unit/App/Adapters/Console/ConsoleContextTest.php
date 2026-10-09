<?php

namespace Quantum\Tests\Unit\App\Adapters\Console;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\Enums\ExitCode;
use Quantum\App\AppContext;
use Quantum\App\App;

class ConsoleContextTest extends AppTestCase
{
    private AppContext $appContext;

    private Application $application;

    private ArrayInput $input;

    private BufferedOutput $output;

    private ConsoleContext $consoleContext;

    public function setUp(): void
    {
        parent::setUp();

        $this->appContext = App::getContext();
        $this->application = new Application();
        $this->input = new ArrayInput([]);
        $this->output = new BufferedOutput();

        $this->consoleContext = new ConsoleContext($this->appContext, $this->application, $this->input, $this->output);
    }

    public function testConsoleContextExposesAppContext(): void
    {
        $this->assertSame($this->appContext, $this->consoleContext->getAppContext());
    }

    public function testConsoleContextExposesApplicationInputAndOutput(): void
    {
        $this->assertSame($this->application, $this->consoleContext->getApplication());
        $this->assertSame($this->input, $this->consoleContext->getInput());
        $this->assertSame($this->output, $this->consoleContext->getOutput());
    }

    public function testConsoleContextDefaultsExitCodeToSuccess(): void
    {
        $this->assertSame(ExitCode::SUCCESS, $this->consoleContext->getExitCode());
    }

    public function testConsoleContextStoresExitCode(): void
    {
        $this->consoleContext->setExitCode(ExitCode::INVALID);

        $this->assertSame(ExitCode::INVALID, $this->consoleContext->getExitCode());
    }
}
