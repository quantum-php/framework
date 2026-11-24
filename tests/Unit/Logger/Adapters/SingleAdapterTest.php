<?php

namespace Quantum\Tests\Unit\Logger\Adapters;

use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Logger\Adapters\SingleAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\ConfigSection;
use RuntimeException;
use Stringable;

class SingleAdapterTest extends AppTestCase
{
    private const LINE_START = '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] ';

    private SingleAdapter $adapter;

    private string $logFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->logFile = base_dir() . DS . 'logs' . DS . 'app.log';

        if ($this->fs->exists($this->logFile)) {
            $this->fs->remove($this->logFile);
        }

        $this->adapter = new SingleAdapter(new ConfigSection([
            'path' => $this->logFile,
        ]));
    }

    public function tearDown(): void
    {
        if ($this->fs->exists($this->logFile)) {
            $this->fs->remove($this->logFile);
        }

        parent::tearDown();
    }

    public function testSingleAdapterInstance(): void
    {
        $this->assertInstanceOf(SingleAdapter::class, $this->adapter);
    }

    public function testSingleAdapterConstructorThrowsExceptionForInvalidPath(): void
    {
        $this->expectException(LoggerException::class);

        new SingleAdapter(new ConfigSection([
            'path' => base_dir() . DS . 'invalid_path',
        ]));
    }

    public function testSingleAdapterReportWritesToFile(): void
    {
        $level = 'info';
        $message = 'Test log message';
        $context = [];

        $this->adapter->report($level, $message, $context);

        $this->assertTrue($this->fs->exists($this->logFile));

        $logContent = $this->fs->get($this->logFile);
        $this->assertStringContainsString('Test log message', $logContent);
    }

    public function testSingleAdapterWritesOnlyTheMessageWithoutContext(): void
    {
        $this->adapter->report('info', 'Order paid');

        $this->assertMatchesRegularExpression(self::LINE_START . 'Info: Order paid' . preg_quote(PHP_EOL, '/') . '$/', $this->written());
    }

    public function testSingleAdapterWritesTheContextAsJsonAfterTheMessage(): void
    {
        $this->adapter->report('info', 'Order paid', ['order' => 1042, 'status' => 'paid']);

        $this->assertMatchesRegularExpression(
            self::LINE_START . preg_quote('Info: Order paid {"order":1042,"status":"paid"}' . PHP_EOL, '/') . '$/',
            $this->written()
        );
    }

    public function testSingleAdapterKeepsTheTraceOnItsOwnLine(): void
    {
        $this->adapter->report('error', 'Failed', ['trace' => '#0 main']);

        $this->assertMatchesRegularExpression(
            self::LINE_START . preg_quote('Error: Failed' . PHP_EOL . '#0 main' . PHP_EOL, '/') . '$/',
            $this->written()
        );
    }

    public function testSingleAdapterWritesTheContextBeforeTheTrace(): void
    {
        $this->adapter->report('error', 'Failed', ['task' => 'cleanup', 'trace' => '#0 main']);

        $this->assertMatchesRegularExpression(
            self::LINE_START . preg_quote('Error: Failed {"task":"cleanup"}' . PHP_EOL . '#0 main' . PHP_EOL, '/') . '$/',
            $this->written()
        );
    }

    public function testSingleAdapterWritesAnExceptionInTheContextReadably(): void
    {
        $exception = new RuntimeException('Disk full');

        $this->adapter->report('error', 'Failed', ['exception' => $exception]);

        $expected = json_encode([
            'exception' => [
                'class' => RuntimeException::class,
                'message' => 'Disk full',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->assertStringContainsString('Error: Failed ' . $expected . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesAStringableValueAsItsText(): void
    {
        $value = new class () implements Stringable {
            public function __toString(): string
            {
                return 'order-1042';
            }
        };

        $this->adapter->report('info', 'Order paid', ['order' => $value]);

        $this->assertStringContainsString('Info: Order paid {"order":"order-1042"}' . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesPathsAndUnicodeReadably(): void
    {
        $this->adapter->report('info', 'Saved', ['file' => '/var/log/café.txt']);

        $this->assertStringContainsString('Info: Saved {"file":"/var/log/café.txt"}' . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesTheLineWhenTheContextHasInvalidUtf8(): void
    {
        $this->adapter->report('info', 'Received', ['name' => "caf\xE9"]);

        $this->assertStringContainsString("Info: Received {\"name\":\"caf\u{FFFD}\"}" . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesNullForAValueJsonCannotEncode(): void
    {
        $resource = fopen('php://memory', 'r');

        $this->adapter->report('info', 'Opened', ['handle' => $resource, 'mode' => 'r']);

        fclose($resource);

        $this->assertStringContainsString('Info: Opened {"handle":null,"mode":"r"}' . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesTheLineWhenAContextValueCannotBeConverted(): void
    {
        $value = new class () implements Stringable {
            public function __toString(): string
            {
                throw new RuntimeException('Not loaded');
            }
        };

        $this->adapter->report('error', 'Failed', ['user' => $value]);

        $this->assertStringContainsString('Error: Failed {"context":"not encodable"}' . PHP_EOL, $this->written());
    }

    public function testSingleAdapterWritesATraceThatIsNotTextInTheContext(): void
    {
        $this->adapter->report('error', 'Failed', ['trace' => ['#0 main', '#1 run']]);

        $this->assertMatchesRegularExpression(
            self::LINE_START . preg_quote('Error: Failed {"trace":["#0 main","#1 run"]}' . PHP_EOL, '/') . '$/',
            $this->written()
        );
    }

    public function testSingleAdapterWritesAStringableTraceOnItsOwnLine(): void
    {
        $trace = new class () implements Stringable {
            public function __toString(): string
            {
                return '#0 main';
            }
        };

        $this->adapter->report('error', 'Failed', ['trace' => $trace]);

        $this->assertMatchesRegularExpression(
            self::LINE_START . preg_quote('Error: Failed' . PHP_EOL . '#0 main' . PHP_EOL, '/') . '$/',
            $this->written()
        );
    }

    private function written(): string
    {
        return $this->fs->get($this->logFile);
    }
}
