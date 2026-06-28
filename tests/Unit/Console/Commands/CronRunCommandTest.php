<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Symfony\Component\Console\Tester\CommandTester;
use Quantum\Console\Commands\CronRunCommand;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Cron\CronLock;

/**
 * Class CronRunCommandTest
 */
class CronRunCommandTest extends AppTestCase
{
    private string $cronDirectory;
    private string $lockDirectory;

    public function setUp(): void
    {
        parent::setUp();

        $this->cronDirectory = base_dir() . DS . 'cron-command-tests';
        $this->lockDirectory = base_dir() . DS . 'cron-command-locks';
        $this->cleanupDirectory($this->cronDirectory);
        $this->cleanupDirectory($this->lockDirectory);
        mkdir($this->cronDirectory, 0777, true);
        mkdir($this->lockDirectory, 0777, true);

        config()->set('logging', [
            'default' => 'single',
            'single' => [
                'driver' => 'single',
                'path' => base_dir() . '/logs',
                'level' => 'debug',
            ],
        ]);

        config()->set('cron', [
            'path' => null,
            'lock_path' => $this->lockDirectory,
            'max_lock_age' => 86400,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanupDirectory($this->cronDirectory);
        $this->cleanupDirectory($this->lockDirectory);
    }

    public function testCommandExecutesSuccessfully(): void
    {
        $this->createTaskFile('test-task.php', [
            'name' => 'test-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $this->cronDirectory]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Running scheduled tasks', $output);
        $this->assertStringContainsString('Execution Summary', $output);
    }

    public function testCommandWithNoTasks(): void
    {
        $emptyDir = $this->cronDirectory . '-empty';
        $this->cleanupDirectory($emptyDir);
        mkdir($emptyDir, 0777, true);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $emptyDir]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('No tasks found', $output);
    }

    public function testCommandWithSpecificTask(): void
    {
        $this->createTaskFile('specific-task.php', [
            'name' => 'specific-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--path' => $this->cronDirectory,
            '--task' => 'specific-task',
        ]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Running task: specific-task', $output);
    }

    public function testCommandWithForceOption(): void
    {
        $this->createTaskFile('force-task.php', [
            'name' => 'force-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--path' => $this->cronDirectory,
            '--force' => true,
        ]);

        $this->assertEquals(0, $tester->getStatusCode());
    }

    public function testCommandWithNonExistentTask(): void
    {
        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--path' => $this->cronDirectory,
            '--task' => 'non-existent',
        ]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('not found', $output);
    }

    public function testCommandDisplaysStatistics(): void
    {
        $this->createTaskFile('task1.php', [
            'name' => 'task-1',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $this->createTaskFile('task2.php', [
            'name' => 'task-2',
            'expression' => '0 0 1 1 *',
            'callback' => function (): void {
            },
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $this->cronDirectory]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Total tasks:', $output);
        $this->assertStringContainsString('Executed:', $output);
        $this->assertStringContainsString('Skipped:', $output);
    }

    public function testCommandHandlesTaskFailure(): void
    {
        $this->createTaskFile('failing-task.php', [
            'name' => 'failing-task',
            'expression' => '* * * * *',
            'body' => "throw new \\Exception('Task failed');",
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $this->cronDirectory]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Failed: 1', $output);
    }

    public function testCommandShortOptions(): void
    {
        $this->createTaskFile('short-option-task.php', [
            'name' => 'short-option-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            '--path' => $this->cronDirectory,
            '-t' => 'short-option-task',
        ]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('short-option-task', $output);
    }

    public function testCommandUsesConfiguredPath(): void
    {
        $this->createTaskFile('config-task.php', [
            'name' => 'config-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        config()->set('cron', [
            'path' => $this->cronDirectory,
            'lock_path' => null,
            'max_lock_age' => 86400,
        ]);

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Execution Summary', $output);
    }

    public function testCommandReportsLockedTasks(): void
    {
        $this->createTaskFile('locked-task.php', [
            'name' => 'locked-task',
            'expression' => '* * * * *',
            'callback' => function (): void {
            },
        ]);

        $lock = new CronLock('locked-task', $this->lockDirectory);
        $lock->acquire();

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $this->cronDirectory]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Locked: 1', $output);

        $lock->release();
    }

    public function testCommandHandlesUnexpectedError(): void
    {
        $invalidTask = $this->cronDirectory . DS . 'invalid-task.php';
        file_put_contents($invalidTask, "<?php\nreturn [;\n");

        $command = new CronRunCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--path' => $this->cronDirectory]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Unexpected error', $output);
    }

    private function createTaskFile(string $filename, array $definition): void
    {
        $body = $definition['body'] ?? 'return true;';

        $content = "<?php\n\nreturn [\n";
        $content .= "    'name' => '{$definition['name']}',\n";
        $content .= "    'expression' => '{$definition['expression']}',\n";
        $content .= "    'callback' => function () {\n";
        $content .= "        {$body}\n";
        $content .= "    }\n";
        $content .= "];\n";

        file_put_contents($this->cronDirectory . DS . $filename, $content);
    }

    private function cleanupDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . DS . $item;

            if (is_dir($path)) {
                $this->cleanupDirectory($path);
            } elseif (file_exists($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
