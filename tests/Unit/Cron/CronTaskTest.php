<?php

namespace Quantum\Tests\Unit\Cron;

use Quantum\Cron\Exceptions\CronException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Cron\CronTask;

/**
 * Class CronTaskTest
 */
class CronTaskTest extends AppTestCase
{
    public function testConstructorWithValidExpression(): void
    {
        $task = new CronTask('test-task', '* * * * *', function (): void {
        });

        $this->assertEquals('test-task', $task->getName());
        $this->assertEquals('* * * * *', $task->getExpression());
    }

    public function testConstructorWithInvalidExpression(): void
    {
        $this->expectException(CronException::class);
        $this->expectExceptionMessage('Invalid cron expression');

        new CronTask('test-task', 'invalid', function (): void {
        });
    }

    public function testShouldRunEveryMinute(): void
    {
        $task = new CronTask('test-task', '* * * * *', function (): void {
        });

        $this->assertTrue($task->shouldRun());
    }

    public function testShouldNotRunFutureTask(): void
    {
        // Task scheduled for next year
        $task = new CronTask('test-task', '0 0 1 1 *', function (): void {
        });

        $this->assertFalse($task->shouldRun());
    }

    public function testHandleExecutesCallback(): void
    {
        $executed = false;

        $task = new CronTask('test-task', '* * * * *', function () use (&$executed): void {
            $executed = true;
        });

        $task->handle();

        $this->assertTrue($executed);
    }

    public function testHandleWithCallbackArguments(): void
    {
        $result = null;

        $task = new CronTask('test-task', '* * * * *', function () use (&$result): void {
            $result = 'executed';
        });

        $task->handle();

        $this->assertEquals('executed', $result);
    }

    public function testGetNextRunDate(): void
    {
        $task = new CronTask('test-task', '0 0 * * *', function (): void {
        });

        $nextRun = $task->getNextRunDate();

        $this->assertInstanceOf(\DateTime::class, $nextRun);
        $this->assertGreaterThan(new \DateTime(), $nextRun);
    }

    public function testGetPreviousRunDate(): void
    {
        $task = new CronTask('test-task', '0 0 * * *', function (): void {
        });

        $previousRun = $task->getPreviousRunDate();

        $this->assertInstanceOf(\DateTime::class, $previousRun);
        $this->assertLessThan(new \DateTime(), $previousRun);
    }

    public function testComplexCronExpression(): void
    {
        // Every 5 minutes
        $task = new CronTask('test-task', '*/5 * * * *', function (): void {
        });

        $this->assertEquals('*/5 * * * *', $task->getExpression());
    }

    public function testWeeklyCronExpression(): void
    {
        // Every Monday at 9 AM
        $task = new CronTask('test-task', '0 9 * * 1', function (): void {
        });

        $this->assertEquals('0 9 * * 1', $task->getExpression());
    }
}
