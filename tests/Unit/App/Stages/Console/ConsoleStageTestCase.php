<?php

namespace Quantum\Tests\Unit\App\Stages\Console;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Application;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Adapters\Console\ConsoleContext;
use Quantum\App\App;

abstract class ConsoleStageTestCase extends AppTestCase
{
    /**
     * Creates a console context around a real Symfony application that neither exits nor catches exceptions.
     * @param array<string, string> $input
     */
    protected function createConsoleContext(array $input = [], ?Application $application = null): ConsoleContext
    {
        $application ??= new Application();
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return new ConsoleContext(App::getContext(), $application, new ArrayInput($input), new BufferedOutput());
    }

    /**
     * Creates a temporary command class in the given directory, loads it, runs the test
     * with the command name, and removes the file and any directories it had to create.
     */
    protected function withTemporaryCommand(string $directory, string $namespace, callable $test): void
    {
        $suffix = substr(md5(uniqid('', true)), 0, 8);
        $className = 'TmpStageCommand' . $suffix;
        $commandName = 'tmp:stage' . $suffix;
        $file = $directory . DS . $className . '.php';

        $missing = [];

        for ($dir = $directory; !is_dir($dir); $dir = dirname($dir)) {
            array_unshift($missing, $dir);
        }

        $command = <<<PHP
<?php

namespace {$namespace};

use Quantum\Console\CliCommand;

class {$className} extends CliCommand
{
    protected ?string \$name = '{$commandName}';
    protected ?string \$description = 'Temporary command';
    protected ?string \$help = 'Created by a test';

    public function exec(): void
    {
    }
}
PHP;

        try {
            if ($missing !== []) {
                $this->assertTrue(mkdir($directory, 0777, true));
            }

            $this->assertNotFalse(file_put_contents($file, $command));

            require_once $file;

            $test($commandName);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }

            foreach (array_reverse($missing) as $dir) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }
        }
    }
}
