<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Debugger;

use Quantum\Database\Contracts\RelationalInterface;
use DebugBar\DataCollector\RequestDataCollector;
use Quantum\Config\Exceptions\ConfigException;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DataCollector\MessagesCollector;
use Quantum\Database\Contracts\DbalInterface;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\MemoryCollector;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Mailer\Factories\MailerFactory;
use Quantum\App\Exceptions\BaseException;
use Quantum\View\Factories\ViewFactory;
use Quantum\Di\Exceptions\DiException;
use DebugBar\JavascriptRenderer;
use DebugBar\DebugBarException;
use Quantum\Database\Database;
use ReflectionException;
use DebugBar\DebugBar;
use Quantum\Di\Di;

/**
 * Class Debugger
 * @package Quantum\Debugger
 * @uses DebugBar
 */
class Debugger
{
    /**
     * Messages tab
     */
    public const MESSAGES = 'messages';

    /**
     * Queries tab
     */
    public const QUERIES = 'queries';

    /**
     * Routes tab
     */
    public const ROUTES = 'routes';

    /**
     * Events tab
     */
    public const EVENTS = 'events';

    /**
     * Mails tab
     */
    public const MAILS = 'mails';

    /**
     * Store
     */
    private readonly DebuggerStore $store;

    /**
     * DebugBar instance
     */
    private readonly DebugBar $debugBar;

    /**
     * Assets url
     */
    private string $assetsUrl = '/assets/DebugBar/Resources';

    /**
     * Custom CSS
     */
    private string $customCss = 'custom_debugbar.css';

    /**
     * @param array<mixed> $collectors
     * @throws DebugBarException
     */
    public function __construct(?DebuggerStore $store = null, ?DebugBar $debugBar = null, array $collectors = [])
    {
        $this->store = $store ?? new DebuggerStore();
        $this->debugBar = $debugBar ?? new DebugBar();

        $collectors = $collectors ?: self::getDefaultCollectors();

        foreach ($collectors as $collector) {
            $this->debugBar->addCollector($collector);
        }
    }

    /**
     * Checks if debug bar enabled
     * @throws DiException|ReflectionException
     */
    public function isEnabled(): bool
    {
        return config()->getBool('app.debug');
    }

    public function initStore(): void
    {
        $this->store->init([
            Debugger::MESSAGES,
            Debugger::QUERIES,
            Debugger::ROUTES,
            Debugger::EVENTS,
            Debugger::MAILS,
        ]);
    }

    /**
     * Adds data to the store cell
     * @param mixed $data
     */
    public function addToStoreCell(string $cell, string $level, $data): void
    {
        if (!empty($data)) {
            $this->store->set($cell, [$level => $data]);
        }
    }

    /**
     * @return array<mixed>
     */
    public function getStoreCell(string $cell): array
    {
        return $this->store->get($cell);
    }

    /**
     * Clears the store cell
     */
    public function clearStoreCell(string $cell): void
    {
        $this->store->delete($cell);
    }

    public function resetStore(): void
    {
        $this->store->flush();
    }

    /**
     * Renders the debug bar. Each tab shows its stored entries, followed by what is collected
     * from the packages at this point.
     * @throws DebugBarException|DiException|ReflectionException|ConfigException|BaseException
     */
    public function render(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        foreach ($this->collect() as $tab => $entries) {
            $this->createTab($tab, $entries);
        }

        $renderer = $this->getRenderer();

        return $renderer->renderHead() . $renderer->render();
    }

    /**
     * Collects the entries of each tab from the packages, as they are now
     * @return array<string, array<int, array<string, mixed>>>
     * @throws DiException|ReflectionException|ConfigException|BaseException
     */
    protected function collect(): array
    {
        $routeInfo = $this->routeInfo();
        $listeners = event()->getRegistered();

        return [
            self::MESSAGES => $this->logMessages(),
            self::QUERIES => array_map(fn ($query): array => ['info' => $query], $this->queryLog()),
            self::ROUTES => $routeInfo !== [] ? [['info' => $routeInfo]] : [],
            self::EVENTS => $listeners !== [] ? [['info' => $listeners]] : [],
            self::MAILS => $this->mailLog(),
        ];
    }

    /**
     * The transport log of the mailer, only when the request used it
     * @return array<int, array<string, string>>
     * @throws DiException|ReflectionException|ConfigException|BaseException
     */
    protected function mailLog(): array
    {
        if (!Di::isRegistered(MailerFactory::class)) {
            return [];
        }

        return array_map(fn (string $line): array => ['warning' => $line], mailer()->getAdapter()->getTransportLog());
    }

    /**
     * The messages of the debug-mode logger
     * @return array<int, array<string, mixed>>
     * @throws DiException|ReflectionException|ConfigException|BaseException
     */
    protected function logMessages(): array
    {
        $adapter = logger()->getAdapter();

        return $adapter instanceof MessageAdapter ? $adapter->getMessages() : [];
    }

    /**
     * The matched route and the rendered view, without the values that are not set
     * @return array<string, mixed>
     * @throws DiException|ReflectionException|ConfigException|BaseException
     */
    protected function routeInfo(): array
    {
        $info = [];

        $matchedRoute = request()->getMatchedRoute();

        if ($matchedRoute !== null) {
            $route = $matchedRoute->getRoute();

            $info = [
                'Methods' => implode(', ', $route->getMethods()),
                'Pattern' => $route->getPattern(),
                'Module' => $route->getModule(),
                'Controller' => $route->getController(),
                'Action' => $route->getClosure() !== null ? 'Closure' : $route->getAction(),
                'Name' => $route->getName(),
                'Group' => $route->getGroup(),
                'Prefix' => $route->getPrefix(),
                'Middlewares' => $route->getMiddlewares(),
                'Params' => $matchedRoute->getParams(),
            ];
        }

        $viewFile = Di::isRegistered(ViewFactory::class) ? ViewFactory::get()->getViewFile() : null;

        if ($viewFile !== null) {
            $info['View'] = request()->getCurrentModule() . '/Views/' . $viewFile;
        }

        return array_filter($info, fn (string|array|null $value): bool => $value !== null && $value !== []);
    }

    /**
     * The queries run so far, only when the request started the database, it is still connected
     * and its adapter keeps a query log
     * @return array<mixed>
     * @throws DiException|ReflectionException
     */
    protected function queryLog(): array
    {
        if (!Di::has(Database::class)) {
            return [];
        }

        $ormClass = db()->getOrmClass();

        if (!is_subclass_of($ormClass, DbalInterface::class) || !$ormClass::getConnection()) {
            return [];
        }

        if (!is_subclass_of($ormClass, RelationalInterface::class)) {
            return [];
        }

        return Database::queryLog();
    }

    /**
     * Creates a tab with its stored entries, followed by the collected ones
     * @param array<int, array<string, mixed>> $collected
     * @throws DebugBarException
     */
    protected function createTab(string $type, array $collected): void
    {
        $collector = $this->resolveMessagesCollector($type);

        foreach (array_merge($this->store->get($type), $collected) as $message) {
            $fn = key($message);
            $collector->log($fn, $message[$fn]);
        }
    }

    /**
     * Ensures the requested debug tab collector can accept mixed message payloads.
     * @throws DebugBarException
     */
    protected function resolveMessagesCollector(string $type): MessagesCollector
    {
        if (!$this->debugBar->hasCollector($type)) {
            $this->debugBar->addCollector(new MessagesCollector($type));
        }

        $collector = $this->debugBar->getCollector($type);

        if (!$collector instanceof MessagesCollector) {
            throw new DebugBarException("Collector '{$type}' must be a MessagesCollector instance");
        }

        return $collector;
    }

    /**
     * Gets the renderer
     */
    protected function getRenderer(): JavascriptRenderer
    {
        return $this->debugBar
            ->getJavascriptRenderer()
            ->setBaseUrl(base_url() . $this->assetsUrl)
            ->addAssets([$this->customCss], []);
    }

    /**
     * @return array<mixed>
     */
    protected static function getDefaultCollectors(): array
    {
        return [
            new PhpInfoCollector(),
            new MessagesCollector(),
            new RequestDataCollector(),
            new TimeDataCollector(),
            new MemoryCollector(),
        ];
    }

}
