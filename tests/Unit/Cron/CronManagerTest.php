<?php

namespace Quantum\Tests\Unit\Cron;

use Quantum\Cron\Exceptions\CronException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Cron\CronManager;

/**
 * Class CronManagerTest
 */
class CronManagerTest extends AppTestCase
{
    private string $cronDirectory;
    private static array $executedTasks = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->cronDirectory = base_dir() . DS . 'cron-tests';
        $this->cleanupDirectory($this->cronDirectory);
        mkdir($this->cronDirectory, 0777, true);
        self::$executedTasks = [];

        // Setup logging config to avoid Loader dependency
        if (!config()->has('logging')) {
            config()->set('logging', [
                'default' => 'single',
                'single' => [
                    'driver' => 'single',
                    'path' => base_dir() . '/logs',
                    'level' => 'debug',
                ],
            ]);
        }

        config()->set('cron', [
            'path' => null,
            'lock_path' => null,
            'max_lock_age' => 86400,
        ]);
    }

    public function testLoadTasksFromDirectory(): void
    {
        $this->createTaskFile('task1.php', [
            'name' => 'task-1',
            'expression' => '* * * * *',
        ]);

        $this->createTaskFile('task2.php', [
            'name' => 'task-2',
            'expression' => '0 * * * *',
        ]);

        $manager = new CronManager($this->cronDirectory);
        $manager->loadTasks();

        $tasks = $manager->getTasks();

        $this->assertCount(2, $tasks);
        $this->assertArrayHasKey('task-1', $tasks);
        $this->assertArrayHasKey('task-2', $tasks);
    }

    public function testLoadTasksWithObjectFormat(): void
    {
        $taskContent = '<?php
            use Quantum\Cron\CronTask;
            return new CronTask("object-task", "* * * * *", function() {});
        ';

        file_put_contents($this->cronDirectory . '/object-task.php', $taskContent);

        $manager = new CronManager($this->cronDirectory);
        $manager->loadTasks();

        $tasks = $manager->getTasks();

        $this->assertCount(1, $tasks);
        $this->assertArrayHasKey('object-task', $tasks);
    }

    public function testLoadTasksWithEmptyDirectory(): void
    {
        $manager = new CronManager($this->cronDirectory);
        $manager->loadTasks();

        $this->assertCount(0, $manager->getTasks());
    }

    public function testRunDueTasksExecutesOnlyDueTasks(): void
    {
        $this->createTaskFile('due-task.php', [
            'name' => 'due-task',
            'expression' => '* * * * *', // Always due
            'body' => "\\Quantum\\Tests\\Unit\\Cron\\CronManagerTest::recordExecution('due-task');",
        ]);

        $this->createTaskFile('not-due-task.php', [
            'name' => 'not-due-task',
            'expression' => '0 0 1 1 *', // Once a year (Jan 1st)
            'body' => "\\Quantum\\Tests\\Unit\\Cron\\CronManagerTest::recordExecution('not-due-task');",
        ]);

        $manager = new CronManager($this->cronDirectory);
        $stats = $manager->runDueTasks();

        $this->assertContains('due-task', self::$executedTasks);
        $this->assertNotContains('not-due-task', self::$executedTasks);
        $this->assertEquals(1, $stats['executed']);
        $this->assertEquals(1, $stats['skipped']);
    }

    public function testRunTaskByName(): void
    {
        $this->createTaskFile('specific-task.php', [
            'name' => 'specific-task',
            'expression' => '* * * * *',
            'body' => "\\Quantum\\Tests\\Unit\\Cron\\CronManagerTest::recordExecution('specific-task');",
        ]);

        $manager = new CronManager($this->cronDirectory);
        $manager->runTaskByName('specific-task');

        $this->assertContains('specific-task', self::$executedTasks);
    }

    public function testRunTaskByNameThrowsExceptionForNonExistentTask(): void
    {
        $this->expectException(CronException::class);
        $this->expectExceptionMessage('not found');

        $manager = new CronManager($this->cronDirectory);
        $manager->runTaskByName('non-existent-task');
    }

    public function testRunDueTasksWithForceIgnoresLocks(): void
    {
        $this->createTaskFile('locked-task.php', [
            'name' => 'locked-task',
            'expression' => '* * * * *',
            'body' => "\\Quantum\\Tests\\Unit\\Cron\\CronManagerTest::recordExecution('locked-task');",
        ]);

        $manager = new CronManager($this->cronDirectory);

        // Run twice with force - should execute both times
        $manager->runDueTasks(true);
        $manager->runDueTasks(true);

        $occurrences = array_filter(self::$executedTasks, fn ($task): bool => $task === 'locked-task');
        $this->assertCount(2, $occurrences);
    }

    public function testTaskExecutionFailureIsHandled(): void
    {
        $this->createTaskFile('failing-task.php', [
            'name' => 'failing-task',
            'expression' => '* * * * *',
            'body' => "throw new \\Exception('Task failed');",
        ]);

        $manager = new CronManager($this->cronDirectory);
        $stats = $manager->runDueTasks();

        $this->assertEquals(0, $stats['executed']);
        $this->assertEquals(1, $stats['failed']);
    }

    public function testGetStatsReturnsCorrectStatistics(): void
    {
        $this->createTaskFile('task1.php', [
            'name' => 'task-1',
            'expression' => '* * * * *',
        ]);

        $this->createTaskFile('task2.php', [
            'name' => 'task-2',
            'expression' => '0 0 1 1 *',
        ]);

        $manager = new CronManager($this->cronDirectory);
        $stats = $manager->runDueTasks();

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['executed']);
        $this->assertEquals(1, $stats['skipped']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(0, $stats['locked']);
    }

    public function testInvalidTaskFileThrowsException(): void
    {
        $this->expectException(CronException::class);
        $this->expectExceptionMessage('Invalid task file');

        file_put_contents($this->cronDirectory . '/invalid.php', '<?php return "invalid";');

        $manager = new CronManager($this->cronDirectory);
        $manager->loadTasks();
    }

    public function testCronDirectoryCanBeConfigured(): void
    {
        $configuredDir = base_dir() . DS . 'cron-configured';
        $this->cleanupDirectory($configuredDir);
        mkdir($configuredDir, 0777, true);

        $this->createTaskFile('configured-task.php', [
            'name' => 'configured-task',
            'expression' => '* * * * *',
        ], $configuredDir);

        config()->set('cron', [
            'path' => $configuredDir,
            'lock_path' => null,
            'max_lock_age' => 86400,
        ]);

        $manager = new CronManager();
        $manager->loadTasks();

        $this->assertArrayHasKey('configured-task', $manager->getTasks());

        $this->cleanupDirectory($configuredDir);
    }

    public function testCronDirectoryNotFoundThrowsException(): void
    {
        $this->expectException(CronException::class);
        $this->expectExceptionMessage('not found');

        $manager = new CronManager($this->cronDirectory . '/non-existent');
        $manager->loadTasks();
    }

    public function testTaskExecutionFailureIsRecorded(): void
    {
        $this->createTaskFile('failing-task.php', [
            'name' => 'failing-task',
            'expression' => '* * * * *',
            'body' => "throw new \\Exception('Execution failed');",
        ]);

        $manager = new CronManager($this->cronDirectory);
        $stats = $manager->runDueTasks(true);

        $this->assertEquals(1, $stats['failed']);
    }

    private function createTaskFile(string $filename, array $definition, ?string $directory = null): void
    {
        $directory ??= $this->cronDirectory;
        $body = $definition['body'] ?? "\\Quantum\\Tests\\Unit\\Cron\\CronManagerTest::recordExecution('{$definition['name']}');";

        $content = "<?php\n\nreturn [\n";
        $content .= "    'name' => '{$definition['name']}',\n";
        $content .= "    'expression' => '{$definition['expression']}',\n";
        $content .= "    'callback' => function () {\n";
        $content .= "        {$body}\n";
        $content .= "    },\n";
        $content .= "];\n";

        file_put_contents($directory . DS . $filename, $content);
    }

    public static function recordExecution(string $taskName): void
    {
        self::$executedTasks[] = $taskName;
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
