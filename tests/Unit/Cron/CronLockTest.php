<?php

namespace Quantum\Tests\Unit\Cron;

use Quantum\Cron\Exceptions\CronException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Cron\CronLock;

/**
 * Class CronLockTest
 */
class CronLockTest extends AppTestCase
{
    private string $lockDirectory;

    public function setUp(): void
    {
        parent::setUp();

        $this->lockDirectory = base_dir() . DS . 'runtime' . DS . 'cron-lock-tests';
        $this->cleanupDirectory($this->lockDirectory);

        config()->set('cron', [
            'path' => null,
            'lock_path' => null,
            'max_lock_age' => 86400,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanupDirectory($this->lockDirectory);
    }

    public function testConstructorCreatesLockDirectory(): void
    {
        $lock = new CronLock('test-task', $this->lockDirectory);

        $this->assertTrue(is_dir($this->lockDirectory));
        $lock->release();
    }

    public function testAcquireLock(): void
    {
        $lock = new CronLock('test-task', $this->lockDirectory);

        $this->assertTrue($lock->acquire());
        $lock->release();
    }

    public function testCannotAcquireLockedTask(): void
    {
        $lock1 = new CronLock('test-task', $this->lockDirectory);
        $lock1->acquire();

        $lock2 = new CronLock('test-task', $this->lockDirectory);

        $this->assertFalse($lock2->acquire());

        $lock1->release();
    }

    public function testReleaseLock(): void
    {
        $lock = new CronLock('test-task', $this->lockDirectory);
        $lock->acquire();

        $this->assertTrue($lock->release());
        $this->assertFalse($lock->isLocked());
    }

    public function testReleaseWithoutAcquireDoesNotDeleteForeignLock(): void
    {
        $lockPath = $this->lockDirectory . DS . 'foreign-task.lock';
        mkdir($this->lockDirectory, 0777, true);
        file_put_contents($lockPath, 'foreign');

        $lock = new CronLock('foreign-task', $this->lockDirectory);
        $lock->release();

        $this->assertFileExists($lockPath);
        @unlink($lockPath);
    }

    public function testIsLocked(): void
    {
        $lock1 = new CronLock('test-task', $this->lockDirectory);

        $this->assertFalse($lock1->isLocked());

        $lock1->acquire();

        $lock2 = new CronLock('test-task', $this->lockDirectory);
        $this->assertTrue($lock2->isLocked());

        $lock1->release();

        $this->assertFalse($lock2->isLocked());
    }

    public function testMultipleTasksCanHaveSeparateLocks(): void
    {
        $lock1 = new CronLock('task-1', $this->lockDirectory);
        $lock2 = new CronLock('task-2', $this->lockDirectory);

        $this->assertTrue($lock1->acquire());
        $this->assertTrue($lock2->acquire());

        $lock1->release();
        $lock2->release();
    }

    public function testRefreshUpdatesTimestamp(): void
    {
        $lock = new CronLock('refresh-task', $this->lockDirectory);
        $lock->acquire();

        $initial = $lock->getTimestamp();

        sleep(2);
        $this->assertTrue($lock->refresh());

        $updated = $lock->getTimestamp();

        $this->assertGreaterThan($initial, $updated);

        $lock->release();
    }

    public function testTaskNameIsSanitized(): void
    {
        $lock = new CronLock('../bad name', $this->lockDirectory);
        $this->assertTrue($lock->acquire());

        $expectedPath = $this->lockDirectory . DS . 'bad_name.lock';
        $this->assertFileExists($expectedPath);

        $lock->release();
        @unlink($expectedPath);
    }

    public function testStaleLocksAreCleanedUp(): void
    {
        $lockPath = $this->lockDirectory . DS . 'stale-task.lock';
        $this->cleanupDirectory($this->lockDirectory);
        mkdir($this->lockDirectory, 0777, true);
        file_put_contents($lockPath, (string) (time() - 90000));

        new CronLock('stale-task', $this->lockDirectory, 10);

        $this->assertFalse(file_exists($lockPath));
    }

    public function testCleanupSkipsActiveLocks(): void
    {
        $lockPath = $this->lockDirectory . DS . 'active.lock';
        $this->cleanupDirectory($this->lockDirectory);
        mkdir($this->lockDirectory, 0777, true);
        file_put_contents($lockPath, (string) (time() - 90000));

        $handle = fopen($lockPath, 'c+');
        flock($handle, LOCK_EX);
        touch($lockPath, time() - 90000);

        new CronLock('dummy', $this->lockDirectory, 10);

        $this->assertFileExists($lockPath);

        flock($handle, LOCK_UN);
        fclose($handle);

        new CronLock('dummy', $this->lockDirectory, 10);

        $this->assertFileDoesNotExist($lockPath);
    }

    public function testConfigurableLockDirectoryIsUsed(): void
    {
        $customDirectory = base_dir() . DS . 'runtime' . DS . 'cron-custom-locks';
        $this->cleanupDirectory($customDirectory);

        config()->set('cron', [
            'path' => null,
            'lock_path' => $customDirectory,
            'max_lock_age' => 86400,
        ]);

        $lock = new CronLock('custom-task');
        $lock->acquire();
        $lock->release();

        $this->assertTrue(is_dir($customDirectory));

        $this->cleanupDirectory($customDirectory);
    }

    public function testCronLockRefresh(): void
    {
        $lock = new CronLock('refresh-task', $this->lockDirectory);
        $this->assertFalse($lock->refresh()); // Not owned yet

        $lock->acquire();
        $this->assertTrue($lock->refresh());
        $lock->release();
    }

    public function testCronLockSanitization(): void
    {
        $lock = new CronLock('  Space Task / \\ ', $this->lockDirectory);
        $this->assertStringContainsString('Space_Task', $this->getPrivateProperty($lock, 'lockFile'));

        $lock2 = new CronLock('', $this->lockDirectory);
        $this->assertStringContainsString('default.lock', $this->getPrivateProperty($lock2, 'lockFile'));
    }

    public function testCronLockDirectoryRecursion(): void
    {
        $nestedDir = $this->lockDirectory . DS . 'a' . DS . 'b' . DS . 'c';
        $lock = new CronLock('nested-task', $nestedDir);
        $this->assertTrue(fs()->isDirectory($nestedDir));
        $lock->acquire();
        $this->assertTrue(fs()->exists($nestedDir . DS . 'nested-task.lock'));
        $lock->release();
    }

    public function testCronLockEmptyDirectoryThrowsException(): void
    {
        $this->expectException(CronException::class);
        new CronLock('task', '');
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
