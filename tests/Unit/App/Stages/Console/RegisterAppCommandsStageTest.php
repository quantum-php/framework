<?php

namespace Quantum\Tests\Unit\App\Stages\Console;

use Quantum\App\Stages\Console\RegisterAppCommandsStage;

class RegisterAppCommandsStageTest extends ConsoleStageTestCase
{
    public function testRegisterAppCommandsStageAddsCommandsFromSharedDirectory(): void
    {
        $directory = base_dir() . DS . 'shared' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Shared\Commands', function (string $commandName): void {
            $context = $this->createConsoleContext();

            $this->assertFalse($context->getApplication()->has($commandName));

            (new RegisterAppCommandsStage())->process($context);

            $this->assertTrue($context->getApplication()->has($commandName));
        });
    }

    public function testRegisterAppCommandsStageSkipsClassesOutsideTheAppNamespace(): void
    {
        $context = $this->createConsoleContext();
        $commandCount = count($context->getApplication()->all());

        // tests/_root/shared/Commands holds a fixture in the test namespace, not in Shared\Commands.
        (new RegisterAppCommandsStage())->process($context);

        $this->assertCount($commandCount, $context->getApplication()->all());
    }
}
