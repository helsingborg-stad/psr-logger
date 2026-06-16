# PSR Logger

[![Build](https://github.com/helsingborg-stad/psr-logger/actions/workflows/php-test.yml/badge.svg?branch=main)](https://github.com/helsingborg-stad/psr-logger/actions/workflows/php-test.yml)
![Tests](test-badge.svg)
![Coverage](coverage-badge.svg)

A PSR-3 compatible logger factory with namespace breadcrumbs, log-level filtering, and context placeholder interpolation. See the [full PSR-3 specification](https://www.php-fig.org/psr/psr-3/) for the interface contract this library implements.

## Requirements

- PHP **8.2** or higher
- [`psr/log`](https://packagist.org/packages/psr/log) **^2.0 || ^3.0**

## Installation
With composer:
```bash
composer require helsingborg-stad/psr-logger
```

## Basic usage

```php
use PsrLogger\LoggerFactory;
use PsrLogger\Loggers\PhpErrorLogger;
use Psr\Log\LogLevel;

$factory = new LoggerFactory(
    namespace: 'MyPlugin',
    loggers: [['logger' => new PhpErrorLogger(), 'logLevel' => LogLevel::ERROR]]
);

$logger = $factory->createLogger();
$logger->error('Something went wrong');
// → [ERROR]:[MyPlugin]: Something went wrong
$logger->debug('Verbose detail');  // suppressed — below ERROR threshold
$logger->info('Just FYI');         // suppressed — below ERROR threshold
```

## Child loggers (namespace tree)

`createLogger()` returns a `LoggerInterface & LoggerFactoryInterface`, so you can branch the namespace tree by calling `createLogger(['namespace' => '...'])` on any logger.

```php
$app       = $factory->createLogger();
$moduleA   = $app->createLogger(['namespace' => 'ModuleA']);
$component = $moduleA->createLogger(['namespace' => 'Component']);

$app->error('app');
// → [ERROR]:[MyPlugin]: app

$moduleA->error('moduleA');
// → [ERROR]:[MyPlugin/ModuleA]: moduleA

$component->error('component');
// → [ERROR]:[MyPlugin/ModuleA/Component]: component
```

Namespace display defaults to `breadcrumbMaxCount: -1` — all segments are shown. Set `breadcrumbMaxCount` to a positive integer to limit how many are displayed (e.g. `2` shows only the first and last segment).

## Context placeholders

`{key}` tokens in the message are replaced with matching values from the `$context` array.
```php
$logger->error('User {user.name} failed to log in', ['user' => ['name' => 'Alice']]);
// → [ERROR]:[MyPlugin]: User Alice failed to log in

$logger->debug('Payload: {data}', ['data' => ['id' => 1, 'status' => 'fail']]);
// → [DEBUG]:[MyPlugin]: Payload: {
//       "id": 1,
//       "status": "fail"
//   }

$logger->debug('Response: {res}', ['res' => $someObject]);
// → [DEBUG]:[MyPlugin]: Response: {
//       "prop": "value"
//   }
```
- Valid key characters are `A-Za-z0-9_.` — hyphens, whitespace, or other characters leave the token unreplaced
- No whitespace inside braces: `{ name }` is never interpolated
- Dot notation resolves nested arrays (`user.name` → `$context['user']['name']`)
- A flat key (`'user.name'`) takes precedence over dot-notation traversal when both exist in context
- Scalars (`int`, `float`) are cast to string; `Stringable` objects call `__toString()`; arrays and objects are JSON-encoded
- Unresolved keys (missing from context or no matching resolver) are left as the original `{key}` token
- When using custom `resolvers`, the first matching resolver wins — later ones are not evaluated
- Arrays and objects are JSON-encoded with JSON_PRETTY_PRINT by default. Pass a custom jsonResolverFlag per-logger to change the encoding flags

## Custom logger implementation

Any class implementing `LoggerInterface` can be used as a destination. The simplest approach is to extend `Psr\Log\NullLogger` and override only `log()` — `NullLogger` provides no-op implementations of all the level-specific methods (`debug()`, `info()`, etc.) that forward to `log()`.

```php
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

class FileLogger extends NullLogger implements LoggerInterface
{
    public function __construct(private string $path) {}

    public function log($level, string|Stringable $message, array $context = []): void
    {
        file_put_contents($this->path, $message . PHP_EOL, FILE_APPEND);
    }
}

$factory = new LoggerFactory(
    namespace: 'MyPlugin',
    loggers: [['logger' => new FileLogger('/tmp/app.log'), 'logLevel' => LogLevel::DEBUG]]
);
```

The two built-in loggers follow the same pattern:

- **`PhpErrorLogger`** — passes the formatted message to `error_log()`.
- **`InMemoryLogger`** — appends each record to a public `$records` array; useful for testing.


## LoggerFactory Constructor options

| Parameter | Default | Description |
|---|---|---|
| `$namespace` | `'PluginNameSpace'` | Root namespace label |
| `$loggers` | `[['logger' => NullLogger, 'logLevel' => ERROR]]` | One or more logger + level pairs |
| `$logLevel` | `LogLevel::ERROR` | Default threshold for all loggers |

## Per-logger options (inside `$loggers` entries)

| Key | Description |
|---|---|
| `logger` | Any `LoggerInterface` instance |
| `logLevel` | Minimum level for this logger. Available levels (high → low): `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug` |
| `breadcrumbMaxCount` | Max namespace segments shown (`-1` = all, default `-1`) |
| `breadcrumbDirection` | Which end to trim: `'left'` (default) or `'right'` |
| `formatStr` | `sprintf` format string — receives `LEVEL`, `namespace`, `message` |
| `jsonResolverFlag` | `json_encode` flags for array/object placeholders (default `JSON_PRETTY_PRINT`) |
| `resolvers` | Custom `[callable $test, callable $transform][]` pairs for placeholder resolution |
