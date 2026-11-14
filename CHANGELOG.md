# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.

## [Unreleased]

### Added
- Added `RouteBuilder::patch()`, a route helper for the `PATCH` method next to `get()`, `post()`, `put()` and `delete()`. Routes declared with `add($path, 'PATCH', ...)` keep working (#653)

### Changed
- `LocalFileSystemAdapter::glob()` declares its return type, `array|false`, which Rector 2.8.4 requires. A class that extends the adapter and overrides `glob()` without a return type must declare it too. `LocalFilesystemAdapterInterface` is unchanged (#656)
- `CurlAdapter` is split to lower its complexity: the request body building moved to the new `Quantum\HttpClient\PostDataBuilder`, and the parsing of response headers, cookies and body to the new `Quantum\HttpClient\ResponseParser`. Its default curl options and its error state are set in private methods. The behavior and the public methods of `CurlAdapter` and `CurlAdapterInterface` are unchanged (#661)
- `Quantum\Environment\Environment` is split into a contract and traits: the new `Quantum\Environment\Contracts\EnvironmentInterface` declares its public methods, and the code moved to `AppEnvTrait`, `ValueTrait` and `EnvFileTrait` in `Quantum\Environment\Traits`. `Environment` keeps its state and `load()`. The behavior and the public methods are unchanged (#614)

### Deprecated
- The hierarchy flag of `Quantum\Config\Setup` is deprecated and will be removed in 3.2.0, when the fallback from a module's config file to `shared/config` will always be on: the `$hierarchical` constructor argument, `setHierarchy()` and `getHierarchy()`. Passing the argument or calling `setHierarchy()` raises `E_USER_DEPRECATED`; `getHierarchy()` is marked `@deprecated` only, because `Config` still calls it. The constructor argument is now `?bool` with `null` as the default, which means `true`; behavior is unchanged. To pass a module without the argument, use `new Setup($pathPrefix, $fileName, module: $module)` (#618)

### Fixed
- `LangFactory::resolve()` returns the cached adapter before it detects the language, so `_t()` and `current_lang()` no longer repeat the query-parameter, URL-segment and `Accept-Language` checks on every call, and no longer throw `LangException::misconfiguredDefaultConfig()` when an adapter is already cached. No public signature changes (#654)

## [3.1.1] - 2026-11-03

### Added
- Added `Response::back()` and the `back()` helper, which redirect to the referrer and fall back to `base_url()` when the request has none, or when the referrer is not on the application origin (same scheme, host and port), which is compared with `base_url()`: behind a proxy that terminates TLS, or where the `Host` header is not pinned, set `app.base_url`. They take the old input to keep for the next request, read with `old()` as after `redirectWith()`, and an optional status code (`back($data, $code)`) (#540)
- The `DemoWeb` About page asks visitors to star Quantum on GitHub, at the top of the page: a button that links to `https://github.com/quantum-php/framework`, with the current star count from a shields.io badge, which the browser loads from the internet. The texts are translation keys, `common.star.message`, `common.star.button` and `common.star.badge_alt`, which the starter project adds to its `common` lang files in English and Spanish; until a project has them the page shows the key names. Modules generated before keep their page (#542)

### Changed
- **BREAKING:** Converted the constant classes `ReservedKeys`, `AuthKeys`, `Pagination` and the database `Type`, `Key` and `Relation` to PHP enums, and moved the table actions of `Table` into the new `TableAction` enum (#612). An enum case is not its value, so code that needs the text or number reads `->value`, and text that comes from outside is turned into a case with `::from()` or `::tryFrom()`:
  - `ReservedKeys`, `AuthKeys`, `Pagination`, `Type`, `Key` and `Relation` are string-backed enums with the same case names and values, so `Relation::BELONGS_TO`, `Type::INT` and `Key::INDEX` are written as before. An application that uses a case as an array key, compares it with text, or passes it to a `string` parameter must add `->value`, for example `$this->keyFields[AuthKeys::USERNAME->value]`. Code that reads the constants with `ReflectionClass::getConstants()` uses `AuthKeys::cases()`.
  - `Table::addIndex()` takes `Key` and `Table::setAction()` takes `TableAction`, and no longer accept text: `addIndex('name', 'unique')` throws a `TypeError`; write `Key::UNIQUE`. A subclass of `Table` that overrides these methods must declare the enum types.
  - `Table::addColumn()`, `Table::modifyColumn()` and `Column::__construct()` accept a `Type` case or the type as text. A text type is used as before, in upper case, so any SQL type name still works (`'integer'`, `'bigint'`, `'mediumtext'`), including those that `Type` has no case for. A subclass that overrides them must declare `Type|string`.
  - `Table::CREATE`, `Table::ALTER`, `Table::DROP` and `Table::RENAME` are removed: use `TableAction::CREATE`, `TableAction::ALTER`, `TableAction::DROP` and `TableAction::RENAME`.
  - The `type` of a model relation must be a `Relation` case. The text `'hasOne'` is rejected with the message "Relation type `hasOne` is not supported"; write `Relation::HAS_ONE`. `ModelException::unsupportedRelationType()` accepts a `Relation` or text.
  - `Pagination` keeps the four names (`PAGINATION_CLASS`, `PAGINATION_CLASS_ACTIVE`, `PER_PAGE`, `PAGE`) as a string-backed enum, read with `->value`. The numbers `FIRST_PAGE_NUMBER`, `MINIMUM_PAGE_ITEMS_COUNT` and `EDGE_PADDING` moved to `PaginationLimit`, an enum that holds them as constants, read without `->value`.
  - The adapter types, `StatusCode`, `ContentType`, `Env`, `ExitCode` and the `ExceptionMessages` classes stay constant classes.
- The `DemoWeb` and `Toolkit` module templates redirect back with `back()` instead of `redirect(get_referrer() ?? base_url())` and `redirectWith(get_referrer() ?? base_url(), $data)`, so generated code gets the same-origin check. Modules generated before keep their code (#541)
- Twig is a required dependency again: `twig/twig` `^3.27` is in `require` and no longer only suggested, which reverses the 3.0.3 change that made it optional while the framework supported PHP 8.0 (#532). Twig `^3.27` needs PHP 8.1, the minimum since 3.1.0. An application that pins an older Twig must allow `^3.27`. Removed `RendererException::twigNotInstalled()` and the check in `TwigAdapter` that threw it when Twig was missing, and the Twig tests no longer skip (#626)
- The test suite runs on PHPUnit `^10.5` (a development dependency) and `phpunit.xml` uses its schema. It now sets `error_reporting` to `-1`: it was the text `E_ALL & ~E_DEPRECATED`, which PHP reads as `0`, so every PHP warning was hidden in the tests (#622)
- `EnvCommand::exec()`, `Environment`, `Config` and `ViewCache` read the base directory once where they asked for it several times, and `LoadHelpersStage` builds the module helpers path with `modules_dir()`. No behavior change (#633)

### Fixed
- `php qt core:env` no longer prints `.env is successfully generated` and no longer tries to copy the file when `.env.example` is missing: it stops after the error message (#622)
- `flush()` of the session adapters no longer raises a warning when no session is active, so calling it twice, or before a session was started, no longer throws (#622)
- `listDirectory()` of the Dropbox and Google Drive adapters returns `false`, as its contract says, when the API response has no `entries` or `files`. It could return `null` when PHP warnings were not reported (#622)

## [3.1.0] - 2026-10-24

### Added
- Added `Request::expectsJson()`: true when the `Accept` header lists a JSON media type (`application/json` or a `+json` type, parameters and case ignored), or for an AJAX request that accepts any content type (#581)
- Added lifecycle events for the web and console runtime: the runtime stages declare `web.preflight`, `web.route`, `web.not_found`, `web.prepare`, `web.dispatch`, `web.send` and `console.command`, each with `.before` and `.after`, and the `Pipeline` dispatches them with the runtime context as a named `context` payload. A stage that does not run dispatches nothing, a stage that throws dispatches no `.after`, and the closing `web.send` events also follow an OPTIONS or not found response. Boot stages declare no events. `console.command.after` fires only with a Symfony application that does not exit after the command (#536, #538)
- Added adapter-based Lang provider support with `DeepL` and `Google Translate` adapters plus shared remote request/caching infrastructure (#533)
- Added the `is_web_app()` and `is_console_app()` helpers, which tell whether the running application is the web app or the console (#593)

### Changed
- **BREAKING:** Took the debugbar out of the app flow and out of the packages that feed it (#539): no package outside `Quantum\Debugger` references it. Each package keeps its own state, and `Debugger::render()` reads it when the toolbar renders: every tab shows the entries written with `debugbar()->addToStoreCell()`, followed by the event listeners, the matched route and the rendered view, the query log, the log messages and the mailer transport log as they are at that point. The debugger store keeps only those direct writes, and the debugbar builds no database connection or mailer the request did not use.
  - Removed `InitDebuggerStage` (`Quantum\App\Stages\InitDebuggerStage` in 3.0.3) from the web boot. The debugger store creates a tab on its first write; code that wants the tabs up front can call `debugbar()->initStore()`.
  - The `events` tab lists the listeners registered when the toolbar renders, so listeners added later in the request, for example in a controller, are listed too.
  - `View::__construct()` no longer takes a `Debugger`: it is `(Renderer, AssetManager, ViewCache)`. Code that gets the view through `view()` or `ViewFactory` is unaffected. Added `View::getViewFile()`, the last view rendered with `render()`.
  - `MessageAdapter`, the logger in debug mode, keeps its messages in memory (`getMessages()`) instead of writing them into the debugger store, and no longer reads `$context['tab']`: a message logged with a tab is listed in the `messages` tab, and `debugbar()->getStoreCell('messages')` returns only direct writes. It keeps the last `logging.message.limit` messages (default `1000`, `0` keeps every message) and drops the oldest (#593).
  - `MailerInterface` gains `getTransportLog()`, implemented in `MailerTrait`: the errors of failed sends and, for SMTP in debug mode, the exchange with the server. A custom adapter that implements the interface without the trait must add it. A failed send is still logged as a warning. At the start of each send the log is cut to its last `mailer.transport_log_limit` lines (default `1000`, `0` keeps every line), so it holds at most the limit plus the lines of the send in progress (#593).
- **BREAKING:** Reworked the app boot and runtime into pipelines of stages that the adapters run in explicit steps (#537, #538, #575, #576, #579, #583, #589, #593):
  - `AppInterface` gains `boot()`. The adapter constructors only store the context, and `AppFactory` calls `boot()` right after creating the adapter, so `AppFactory::create(...)->start()` is unchanged. Code that creates an adapter directly must call `boot()` before `start()`.
  - Removed the `AppAdapter` base class and the `WebAppTrait` and `ConsoleAppTrait` traits: `WebAppAdapter` and `ConsoleAppAdapter` implement `AppInterface` directly and moved to `Quantum\App\Adapters\Web` and `Quantum\App\Adapters\Console`. The stages are grouped by the adapter that runs them: the boot stages both adapters run in `Quantum\App\Stages\Boot`, the others in `Quantum\App\Adapters\Web\Stages\Boot`, `...\Web\Stages\Runtime`, `...\Console\Stages\Boot` and `...\Console\Stages\Runtime`. Code that goes through `AppFactory` is unaffected; code that references these classes directly must update its imports.
  - Replaced `BootPipeline` and `BootStageInterface` with one generic `Quantum\App\Pipeline<TContext>`, which runs `StageInterface<TContext>` stages in order and dispatches the `BEFORE`/`AFTER` events they declare. The context type is a PHPDoc template checked by PHPStan: a stage states it with `@implements StageInterface<AppContext>` (or `WebContext`, `ConsoleContext`) and takes `process(AppContext $context)`. A stage that calls `stop()` on the context ends the run; an optional closing stage, the second constructor argument, runs last, also after a stop, but not after a stage throws.
  - `AppContext` keeps `getBaseDir()`, `getContainer()`, `getEnvironment()` and `getConfig()`, and carries the state of a run: `stop()`, `isStopped()`, `getExitCode()` and `setExitCode()`. Removed `AppContext::getRequest()`, `getResponse()` and `getRoutes()`: use `WebContext::getRequest()` and `WebContext::getRoutes()`, and `response()` for the shared response. `AppContext` also records the app type: its constructor takes a third, required `$type` (`AppType::WEB` or `AppType::CONSOLE`) and `getType()` returns it. `AppFactory` passes it; code that creates an `AppContext` itself must pass it too.
  - The new `WebContext` and `ConsoleContext` extend `AppContext` with the same base directory, container and app type. `WebContext` reads the request, the routes and the view cache from the container and stores the response to send; `ConsoleContext` reads the Symfony `Application`, the input and the output from the container.
  - The web runtime runs the preflight, route resolution, not found, prepare and dispatch stages, with `SendResponseStage` as its closing stage. Behavior is unchanged.
  - The console boot registers the Symfony `Application`, the input and the output in the container (`InitConsoleStage`), loads the helpers, registers the core commands, loads the environment, the config and the error handler, names the application from `app.name` and `app.version` (`UNKNOWN` without them), and registers the app commands last, so their constructors can use `env()` and `config()`. The console runtime runs the command and rejects a missing or unknown one. Registration goes through the new `Quantum\Console\CommandRegistrar`. Removed `ConsoleAppAdapter::createApplication()`: get the application with `Di::get(Application::class)`.
  - The console no longer decides by the `core:env` command name. Added `CliCommand::$requiresEnvironment` (default `true`) with `CliCommand::requiresEnvironment()`. When the `.env` file is missing or malformed, the new `ResolveEnvironmentStage` stops the boot for a core command that sets it to `false` (`core:env`) and for Symfony's `list` and `help`, so they still run; every other command fails with the exception of the environment file. With a valid `.env`, `core:env` now runs the full boot. A core command constructor must not use the environment or any config, since the core commands are registered before the environment is loaded.
  - The console no longer makes the environment mutable for every command. `core:key` and the toolkit installer already do it themselves; a command outside the framework that calls `environment()->updateRow()` must call `setMutable(true)` first, or it gets `EnvException::environmentImmutable()`.
- **BREAKING:** Replaced the `Quantum\Hook` package with `Quantum\Event` (#536):
  - `hook()` is now `event()`; `on()` and `fire()` are now `listen()` and `dispatch()`, and no aliases remain. Listeners receive one named payload array, which is empty when none is given.
  - Event names no longer need to be registered, so `shared/config/hooks.php` is no longer read; dispatching a name without listeners does nothing and an empty name throws `EventException`.
  - Listeners stay registered after `dispatch()` instead of being removed after the first call.
  - Listeners can be registered from app helper files, which load before the config and before any runtime event.
  - Removed the unused `hooks_dir()` helper and renamed the debugger tab constant `Debugger::HOOKS` to `Debugger::EVENTS`; the debugbar tab is now `events` and lists only events that have listeners.
- **BREAKING:** Removed `Quantum\Loader` and moved its responsibilities to their owning packages (#535):
  - Moved `Setup` to `Quantum\Config\Setup`; required config failures now use `ConfigException`, and optional imports use `Config::importIfExists()`.
  - Config paths resolve from the application base directory, independent of the process working directory.
  - `Environment::load()` no longer accepts `Setup`, reads only `shared/config/env.php`, and reports missing files with `EnvException`.
  - App boot now loads component, app, and module helpers directly.
- Corrected cache adapter deserialization exception handling and `ModelCollection` key type declarations (#570)
- Refactored `HttpClient` internals behind explicit `CurlAdapter` and `MultiCurlAdapter` wrappers while preserving the existing facade methods and keeping `php-curl-class` as the underlying transport for this phase (#534)
- Replaced the single-request `HttpClient` `CurlAdapter` execution path with native PHP cURL (#566)
- Replaced the multi-request `HttpClient` `MultiCurlAdapter` execution path with native PHP multi-curl and removed the `php-curl-class` dependency while preserving facade, factory, helper, callback, and response aggregation behavior (#567)
- Refactored the Lang package to resolve adapter instances through `LangFactory` configuration and load file translations lazily on first use instead of preloading them during web boot (#533)
- **BREAKING:** Reshaped Lang configuration so `lang.default` now selects the adapter, locale fallback moved to `lang.default_locale`, and the unused `lang.enabled` toggle was removed (#533)
- **BREAKING:** Removed `Lang::isEnabled()` from the public Lang API because it no longer affected runtime behavior (#533)
- Applied the pending Rector refactors across `src/` (null-coalescing assignment for lazy initialization, explicit `instanceof` checks instead of truthiness checks on nullable objects, native return types on `env()`, `CurlAdapter::getId()` and `CurlAdapter::getResponseCookies()`) and added `composer rector:check` to the CI workflow after PHPStan (#587)
- In debug mode the console logs to the configured adapter, or the one a caller asks for, at every level. Before, debug mode always used the in-memory `message` adapter, so the console's log lines were never written anywhere, and `CronManager`'s `single` logger was replaced too. The web app keeps the `message` adapter for the debug toolbar (#593)
- The error handler chooses the console or the web output by the app type instead of checking `PHP_SAPI`; `php qt`, `php -S` and PHP-FPM behave as before (#593)
- **BREAKING:** Raised the minimum supported PHP version from `8.0` to `8.1` (#595). Applications on PHP `8.0` stay on 3.0.x.
  - CI tests PHP `8.1`, `8.2` and `8.3`. PHPStan analyses the code as PHP `8.1`, and Rector uses its PHP `8.1` level set.
  - `ResponseHeaders` (`offsetGet()`, `current()`, `key()`: `mixed`) and `DatabaseHandler::gc()` (`int|false`) declare the return types PHP `8.1` expects. Without them each raised a deprecation when the class loaded. A class that extends them and overrides these methods must declare compatible return types.
  - Applied the PHP `8.1` syntax Rector proposes: first-class callable syntax (`array_map(trim(...), $items)`, `$this->handleError(...)`) and `readonly` for 84 private properties that are only assigned in the constructor, so subclasses are unaffected. `ErrorHandler` registers closures with `set_error_handler()` and `set_exception_handler()` instead of array callables (#601).
- **BREAKING:** Moved `symfony/console`, `symfony/var-dumper` and `symfony/var-exporter` to the `^6.4` line and dropped `symfony/polyfill-php73`. An application that subclasses Symfony classes may need the stricter return types of Symfony 6 (#596)
- Allowed `psr/log` `^2.0 || ^3.0`. The nine PSR-3 methods of `Logger` declare `string|Stringable $message`. An override that keeps no parameter type or declares `string|Stringable` still works; PHP rejects one that narrows it, for example to `string`, as it did before (#599)
- **BREAKING:** The API module templates describe the OpenAPI documentation with `#[OA\...]` attributes instead of `@OA\...` docblocks, and `doctrine/annotations` is no longer a dependency (#597). The `DefaultApi` and `DemoApi` templates now document version `3.x`.
  - Modules generated before 3.1 keep their `@OA` docblocks, and without `doctrine/annotations` `install:openapi` fails with `Required @OA\PathItem() not found`. Convert them to attributes (`use OpenApi\Attributes as OA;` and `#[OA\Get(...)]`), or require `doctrine/annotations` in the application.
  - This replaces the 3.0.3 note that kept OpenAPI on annotations for the PHP `8.0` baseline.
- **BREAKING:** The error handler logs a reported deprecation (`E_DEPRECATED` or `E_USER_DEPRECATED`) as a warning (`<message> in <file>:<line>`) and lets the script go on, instead of throwing an `ErrorException` that stopped the request. Other reported errors still throw. Idiorm's query logging in debug mode raised such a deprecation on PHP `8.1` for every `NULL` bound value (#598)

### Fixed
- The page not found response is JSON for every client that asks for JSON, not only when the `Accept` header is exactly `application/json`: clients such as axios (`application/json, text/plain, */*`), a `charset` parameter, `+json` types and AJAX requests used to get the HTML page. `page_not_found_response()` decides with `Request::expectsJson()` and takes an optional request (#581)
- `core:env` now looks for the existing `.env` in the application base directory, so running it from another working directory asks for the confirmation instead of silently overwriting the file (#575)
- The file rate limiter creates the missing parent folders of its `path` (for example `cache/data` when `cache/` does not exist), instead of failing every request that goes through the rate limit middleware with `mkdir(): No such file or directory` (#585)
- The debugbar `routes` tab shows the matched route again (methods, pattern, module, controller, action or `Closure`, name, group, prefix, middlewares and params) next to the rendered view; since 3.0.0 it showed only the view path (#539)
- The debugbar `queries` tab lists the queries the request ran; it has been empty since 2.6.0 (#539)
- `SmtpAdapter` records the SMTP exchange in debug mode: it checked `config()->get('debug')`, which is never set, instead of `app.debug`, so the exchange never reached the `mails` tab (#539)
- Idiorm keeps no query log when debug mode is off. It received `app.debug` unconverted, and `DEBUG=false` from `.env` arrives as the text `"false"`, which PHP reads as true, so every query was kept in memory, also in production (#593)
- `mailer.mail_trap` set from `.env` is read as a boolean: `MAIL_TRAP=false` arrives as the text `"false"`, which kept every mail in the trap instead of sending it (#593)
- A "remember me" login works with `REMEMBER_LIFETIME` set in `.env`: the session auth adapter passed the text on to `Cookie::set()`, which takes an `int`, and threw a `TypeError` (#593)
- The Redis and Memcached cache adapters and the Redis rate limiter accept a `port` set from `.env`: `Memcached::addServer()` takes an `int` and threw a `TypeError` for the text (#593)
- `DatabaseHandler::gc()` returns the number of deleted sessions, as `SessionHandlerInterface::gc()` documents. It returned `0` after any successful delete and `false` when SleekDB had nothing to delete (#595)
- `install:openapi` creates the `resources` folder of a module that has none, for example one generated from the `DefaultApi` template, and the `public/assets/OpenApiUi` folder, instead of failing with `mkdir()` and `copy()` errors (#606)

## [3.0.3] - 2026-07-10

### Changed
- **BREAKING:** Raised the minimum supported PHP version from `7.4` to `8.0` (#532)
- Updated CI coverage to test PHP `8.0`, `8.1`, and `8.2` (#532)
- Raised the static analysis baseline to PHPStan level `8` with PHP `8.0` target settings (#532)
- Kept Rector aligned to the PHP 8 baseline and applied bounded PHP 8-safe modernization updates (#532)
- Simplified the standard framework file banner across core sources, templates, and affected tests (#532)
- Made Twig an optional renderer dependency so the framework can retain PHP 8.0 support without requiring vulnerable Twig releases (#532)
- Upgraded the PHP 8.0-compatible runtime lines for `psr/log`, `php-curl-class`, `php-debugbar`, `phpmailer`, `voku/html-min`, `swagger-ui`, and `swagger-php` (#532)

### Notes
- OpenAPI metadata remains on Doctrine-style annotations for now because the currently used `swagger-php` attribute scanning path requires PHP `8.1+`; `doctrine/annotations` is intentionally retained on the PHP `8.0` baseline (#532)
- Twig rendering requires a separately installed, security-supported Twig release; Twig `^3.27` requires PHP 8.1 or later, while the default HTML renderer remains available on PHP 8.0 (#532)

## [3.0.0] - 2026-05-28

Upgrade guide: https://github.com/softberg/quantum-php-docs/blob/master/v3.0/upgrade-guide.md

### Changed
- **BREAKING:** Refactored app bootstrapping and DI ownership model (#373):
  - Introduced `AppContext` as the central execution state holder (mode, baseDir, DiContainer, Environment, Config, Request, Response, Routes)
  - Split `Di` into a static facade delegating to an instance-based `DiContainer`, one container per application execution
  - Added `Di::set()`, `Di::has()`, and `Di::isRegistered()` for explicit service management
  - `Di::get()` now enforces a strict contract: throws if the dependency is not explicitly registered (no more implicit auto-registration)
  - Introduced `BootPipeline` and `BootStageInterface` for explicit, ordered boot sequences
  - Extracted boot stages: `LoadHelpersStage`, `LoadEnvironmentStage`, `LoadAppConfigStage`, `SetupErrorHandlerStage`, `InitHttpStage`, `InitDebuggerStage`
  - Removed `AppTrait`; all adapter boot logic moved to pipeline stages and focused private methods in `WebAppTrait`/`ConsoleAppTrait`
- **BREAKING:** Converted `Request` and `Response` from static facades to instance-based classes (#454):
  - Merged `HttpRequest`/`HttpResponse` wrapper layer directly into `Request`/`Response`
  - All request/response access now goes through `request()` and `response()` helper functions
- **BREAKING:** Refactored request/response execution flow to explicit `Response` returns (#470, #471, #472, #473):
  - Route handlers (controller actions and closure routes) must now return `Quantum\Http\Response`; non-`Response` returns now fail fast
  - Response builder methods are now fluent and return `Response`/`self` (`json()`, `html()`, `xml()`, `jsonp()`, `set()`, `setHeader()`, `setStatusCode()`, `redirect()`)
  - Redirect helpers now return `Response` (`redirect()`, `redirectWith()`) instead of terminating execution implicitly
  - Middleware pipeline is now terminal/return-driven: middleware `apply()` must return `Response`, and middleware chaining no longer returns `[Request, Response]`
  - Web adapter flow no longer relies on `stop()` for normal control flow (`OPTIONS`, 404, cache-hit, route dispatch) and now sends a returned `Response`
  - Cached view lookup now returns `?Response` directly (`ViewCache::getCachedResponse()`)
  - Console adapter normal termination no longer relies on `StopExecutionException`; `start()` now returns explicit exit codes
- **BREAKING:** Removed all static singleton patterns from core services (#381, #382):
  - Migrated all 12 first-party factories (Auth, Archive, Cache, Captcha, Cryptor, FileSystem, Lang, Logger, Mailer, Renderer, Session, View) from static instance caches to DI-managed lifetimes
  - Migrated service singletons to DI ownership: Cookie, Config, Environment, Server, AssetManager, Csrf, Database, MailTrap, Debugger, ViewCache, ErrorHandler, HookManager, ModuleLoader
- **BREAKING:** `Environment` class is no longer a static singleton (#456):
  - Uses `Dotenv::createArrayBacked()` for isolated, deterministic env loading
  - New `environment()` helper function and shorthand check methods: `isProduction()`, `isTesting()`, `isStaging()`, `isDevelopment()`, `isLocal()`
  - `env()` helper now delegates through `environment()->getValue()`
- **BREAKING:** Renamed `Quantum\\Service\\QtService` to `Quantum\\Service\\Service` (#500)
- **BREAKING:** Renamed `Quantum\\Middleware\\QtMiddleware` to `Quantum\\Middleware\\Middleware` (#500)
- **BREAKING:** Renamed `Quantum\\Migration\\QtMigration` to `Quantum\\Migration\\Migration` (#500)
- **BREAKING:** Renamed `Quantum\\Console\\QtCommand` to `Quantum\\Console\\CliCommand` (#500)

- **BREAKING:** Minimum PHP version requirement raised from 7.3 to 7.4
- Modernized codebase with PHP 7.4+ syntax using Rector:
  - Array destructuring: `list()` → `[]`
  - Arrow functions for simple closures
  - Null coalescing assignment operator (`??=`)
  - Class constant references using `::class`
  - Secure random number generation with `random_int()`
  - Modern `setcookie()` array syntax
- Updated CI pipeline to test against PHP 7.4, 8.0, and 8.1
- CI now fails on PHP warnings and deprecations for stricter quality control
- Added `declare(strict_types=1)` to all Exception classes for improved type safety
- Static analysis baseline is now PHPStan level 7

- **BREAKING:** Refactored routing system internals:
  - Routes are now represented as first-class objects (`Route`, `RouteCollection`, `MatchedRoute`)
  - Introduced `RouteBuilder` as a fluent DSL interpreter and route composition engine
  - Introduced `PatternCompiler` for centralized route pattern compilation
  - Introduced `RouteDispatcher` for explicit controller / closure dispatching
  - Route matching now produces a `MatchedRoute` object containing the route and extracted parameters
  - Routing is executed once per request; matched route is stored on the `Request`
  - Global route helper functions (`current_*`, `route_*`) now rely on `MatchedRoute` instead of legacy static route state
  - Middleware ordering is strictly prepend-based (later middleware wraps earlier)
  - Route name uniqueness is now enforced at build time

- **BREAKING:** Controller resolution behavior changed:
  - Routes no longer implicitly resolve controller class names via legacy `RouteController` logic
  - Controllers are now instantiated directly based on the handler defined in the route
  - Projects relying on legacy controller resolution or `RouteController` static state must update accordingly

- **BREAKING:** Refactored model architecture:
  - Introduced a base Model class for non-database models
  - Refactored QtModel into DbModel with persistence-only responsibilities
  - Database fetch methods (first(), findOne(), findOneBy(), get()) now return new model instances instead of mutating existing ones
  - Calling create() resets model state to ensure clean inserts
  - Database-generated primary keys are now synced into the model after save()
  - Models are hydrated only on fetch operations, not implicitly via getters
- `DbModel::save()` now automatically applies timestamps when the model uses the `HasTimestamps` trait
- `SoftDeletes` now uses model timestamp format when available (datetime/unix) for `deleted_at`
- Routing DSL now supports first-class route rate limiting via `->rateLimit(int $limit, int $interval)`.
- Middleware execution pipeline now includes a framework-level middleware stage before module middleware resolution, used for route rate limit enforcement.

### Fixed
- PHP 8.1 compatibility: Fixed null parameter deprecations in `explode()`, `parse_url()`, and `str_replace()`
- PHP 8.4 forward compatibility: Fixed implicitly nullable parameters in database and auth adapters
- PHP 8.4 forward compatibility: Added missing type hint to `SleekDbal::__get()` magic method
- Fixed deprecated `E_STRICT` constant usage in test bootstrap
- Fixed cURL error message assertions for cross-version compatibility
- Fixed SleekDB paginator query-state regression where `count()` could clear criteria before paginated data fetch on the same model instance (#514)
- Standardized `defineValidationRules(Request $request): void` across DemoWeb and DemoApi middleware templates
- Fixed OpenAPI installer route generation to return `Response` objects via `response()->...` helpers and avoid undefined response-variable usage (#520)
- Standardized `defineValidationRules(Request $request): void` in Toolkit middleware templates
- Fixed request uploaded-file parsing to preserve multiple top-level multipart file fields in both real and internal request flows (#526)
- Fixed `ModuleLoader` to skip dependency registration for modules where `enabled` is `false`, aligning dependency loading with route loading behavior (#529)

### Added
- `AppContext` class representing the runtime identity of a single application execution
- `DiContainer` instance-based dependency injection container, isolated per execution
- `BootPipeline` and `BootStageInterface` for declarative, ordered boot sequences
- `environment()` global helper and `Environment` shorthand methods (`isProduction()`, `isTesting()`, etc.)
- Lazy registration guards (`Di::register()` + `Di::isRegistered()`) at all DI call sites for explicit dependency management
- Rector as dev dependency for automated code refactoring
- Additional PHP extensions required in CI: `bcmath`, `gd`, `zip`
- PHPUnit strict testing flags: `--fail-on-warning`, `--fail-on-risky`

- **Routing test coverage**:
  - Comprehensive unit tests for route building, grouping, middleware ordering, caching, and naming
  - Unit tests for route helpers and request–route integration
  - Unit tests for dispatcher behavior with controller and closure routes

- **Cron Scheduler**: New CLI command `php qt cron:run` for running scheduled tasks
  - Task definition via PHP files in `cron/` directory
  - Cron expression parsing using `dragonmantank/cron-expression` library
  - File-based task locking to prevent concurrent execution
  - Comprehensive logging of task execution and errors
  - Support for force mode and specific task execution
  - Automatic cleanup of stale locks (older than 24 hours)
  - Full documentation in `docs/cron-scheduler.md`

- **Opt-in Model Timestamps**: Introduced `HasTimestamps` trait for `DbModel`:
  - Automatically sets `created_at` on insert
  - Automatically sets `updated_at` on insert and update
  - Supports custom timestamp column names via model constants (`CREATED_AT`, `UPDATED_AT`)
  - Supports datetime and unix timestamp formats via `TIMESTAMP_TYPE`
- **Rate Limiting**:
  - Added `RateLimit` package with contract-first architecture (`RateLimitAdapterInterface`, `RateLimiter`, `RateLimiterFactory`)
  - Added file and redis rate limit adapters
  - Added `RateLimitMiddleware` for centralized `429 Too Many Requests` enforcement
  - Added rate limit response headers (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`)
  - Added route-level metadata support on `Route` and `RouteBuilder` for rate limiting
  - Added unit test coverage for router integration, middleware flow, adapters, factory resolution, and exception behavior
  - Updated `DemoApi` module templates to include default API route throttling and generated `config/rate_limit.php` (#510)
- SleekDB adapter now supports related-model criteria path filtering across join depths while preserving DBAL public API (#508)

### Removed
- Support for PHP 7.3 and earlier versions
- Legacy routing static state and implicit controller resolution via `RouteController`
- `AppTrait` — replaced by boot pipeline stages and adapter-specific traits
- Static singleton patterns from all core services and factories
- `Environment::getInstance()` static singleton accessor
- `HttpRequest`/`HttpResponse` static facade wrapper classes (merged into `Request`/`Response`)
- Implicit auto-registration in `Di::get()` — all dependencies must be explicitly registered
- `RegisterCoreDependenciesStage` and `dependencies.php` — replaced by lazy registration at call sites
