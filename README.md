# Laravel Http Automock

[![Run Tests](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml/badge.svg)](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/michiruf/laravel-http-automock.svg)](https://packagist.org/packages/michiruf/laravel-http-automock)
[![Total Downloads](https://img.shields.io/packagist/dt/michiruf/laravel-http-automock.svg)](https://packagist.org/packages/michiruf/laravel-http-automock)

Automatically record and replay HTTP responses in your Laravel tests. On the first test run, real HTTP requests are
made and the responses are saved to disk. On subsequent runs, the saved responses are used instead, no real requests
are made. This makes your tests faster, deterministic, and independent of external services. Automock is fully
compatible with `Http::fake()`.

Requires PHP 8.2+, Laravel 11+, and [Pest](https://pestphp.com/).
Works only with Laravel's [HTTP client](https://laravel.com/docs/http-client).

## Quick Start

### Installation

```shell
composer require michiruf/laravel-http-automock --dev
```

Optionally publish the config:

```shell
php artisan vendor:publish --tag="http-automock-config"
```

### Usage

Call `Http::automock()` inside your test before executing HTTP requests. Responses will be automatically saved on
the first run and replayed on subsequent runs.

```php
it('can do stuff with the api', function () {
    Http::automock();
    $response = Http::get('https://api.sampleapis.com/coffee/hot')->json();
    expect($response)->toHaveCount(20, 'There are not 20 hot coffees in the api service');
});
```

For more test examples, see the [example test](./tests/Feature/ExampleUsageTest.php).

### How It Works

1. You call `Http::automock()` in a test, which registers the recording/replaying handlers
2. When an HTTP request is made:
    - If a mock file exists for that request, the saved response is returned (no real request)
    - If no mock file exists, the real request is made and the response is saved to disk
3. Mock files are stored per-test in a directory structure like:
   ```
   tests/.pest/automock/TestScope/ExampleTest/it_can_do_stuff_with_the_api/1_GET_4c147242.mock
   ```

## Motivation

When testing applications that depend on external APIs, you often end up manually capturing response data to feed
into `Http::fake()`. This is tedious, especially when services return large or complex payloads. Running real
requests in tests ensures your application actually works against live data, but the execution time is high, and
maintaining both faked and real test setups creates significant overhead.

Laravel Http Automock removes that burden. On the first run, your tests hit the real APIs and responses are saved
automatically. Every subsequent run replays those responses instantly. You get the confidence of real data without
the cost of real requests.

Since mock files live in your repository, git naturally picks up changes in external API responses which makes it easy
to notice when a service changed its behavior while keeping a safe copy of the data.

## Configuration

Each feature can be configured via three methods (in order of precedence):

1. **Fluent API**: `Http::automock()->someFeature()` or `Http::configureAutomock()->someFeature()`
2. **CLI**: `./vendor/bin/pest --automock-option`
3. **Config**: `config/http-automock.php` (if published)

> [!NOTE]
> `Http::automock()` both enables automock and returns the instance for fluent configuration.
> `Http::configureAutomock()` returns the instance without enabling automock, useful for setting options separately.

### Reference

In addition to the publishable config `http-automock.php`, you can use these methods to configure automock:

| Feature                  | Fluent API                                | CLI Flag                                   | Default                   |
|--------------------------|-------------------------------------------|--------------------------------------------|---------------------------|
| Enable                   | `Http::automock()`                        | `--automock-enabled`                       | off                       |
| Disable                  | `...->disable()` / `Http::noAutomock()`   | —                                          | —                         |
| Directory                | —                                         | `--automock-directory=...`                 | `'.pest/automock'`        |
| Extension                | —                                         | `--automock-extension=...`                 | `'.mock'`                 |
| Shared                   | `...->shared()`                           | `--automock-shared`                        | `false`                   |
| Shared directory         | —                                         | `--automock-shared-directory=...`          | `'.pest/automock/Shared'` |
| Prettify dir naming      | `...->prettifyDirectoryNaming()`          | `--automock-prettify-directory-naming`     | `false`                   |
| File name resolver       | `...->resolveFileNameUsing(...)`          | `--automock-file-name-resolver=...`        | `'stack'`                 |
| Headers                  | `...->withHeaders(...)`                   | `--automock-headers=...`                   | off / `[]`                |
| Renew                    | `...->renew()`                            | `--automock-renew`                         | `false`                   |
| Prevent auto renew       | `...->preventAutoRenew()`                 | `--automock-prevent-auto-renew`            | `false`                   |
| Prune                    | `...->prune()`                            | `--automock-prune`                         | `false`                   |
| Prevent real requests    | `...->preventRealRequests()`              | `--automock-prevent-real-requests`         | `false`                   |
| Prevent unknown requests | `...->preventUnknownRealRequests()`       | `--automock-prevent-unknown-real-requests` | `false`                   |
| Skip (URL pattern)       | `...->skip('pattern', 'alias')`           | `--automock-url-filters=...`               | `[]`                      |
| Skip GET/POST/...        | `...->skipGet()`, `...->skipPost()`, etc. | —                                          | —                         |
| Validate mocks           | `...->validateMocks()`                    | `--automock-validate-mocks`                | `false`                   |
| Mock HTTP fakes          | `...->mockHttpFakes()`                    | `--automock-mock-http-fakes`               | `false`                   |
| JSON pretty print        | `...->jsonPrettyPrint()`                  | `--automock-json-pretty-print`             | `true`                    |

### Enable / Disable

Automock is not active by default. It must be explicitly enabled per-test by calling `Http::automock()`. To disable
automock afterward, use `disable()` or `Http::noAutomock()`.

### File Name Resolvers

Each mock file needs a unique filename so that requests can be matched to their saved responses. File name resolvers
control how these filenames are generated from the request properties (URL, method, body, etc.). The default `stack`
resolver produces filenames like `1_GET_4c147242.mock` by combining a request counter, HTTP method, and URL hash.

You can switch to a different resolver, combine multiple resolvers, or provide your own logic entirely - either via
a named resolver from the config, a closure, or a resolver class instance.

Available resolvers: `stack`, `count`, `http_method`, `url_hash`, `url_subdirectory`, `data_hash`

**Using a named resolver:**

```php
Http::automock()->resolveFileNameUsing('url_subdirectory');
// Creates: .../api.example.com/coffee/hot.mock
```

**Using a closure:**

```php
Http::automock()->resolveFileNameUsing(fn (Request $request, bool $forWriting) => "TEST-{$request->method()}");
// Creates: .../TEST-GET.mock
```

**Using a resolver class:**

```php
Http::automock()->resolveFileNameUsing(new RequestUrlResolver(port: false, removeSlashes: false));
// Creates: .../localhost/coffee/hot.mock
```

**Using resolver with custom arguments:**

```php
Http::automock()->resolveFileNameUsingResolverAndArgs(
    RequestUrlResolver::class,
    ['port' => false, 'removeSlashes' => false]
);
```

**Configuring the stack resolver:**

The default `stack` resolver combines multiple resolvers into a single filename, joined by a delimiter. You can
configure which resolvers are used and even apply different stacks based on URL patterns. In `config/http-automock.php`:

```php
'default_filename_resolver' => 'stack',
'filename_resolvers' => [

    'stack' => [
        'resolver' => \HttpAutomock\Resolver\StackResolver::class,
        'filenameResolvers' => [
            // '*' matches all URLs, use specific patterns to customize per host
            '*' => ['count', 'http_method', 'url_hash'],
        ],
        'delimiter' => '_',
    ],

    // Individual resolvers referenced above
    'count'            => \HttpAutomock\Resolver\CountResolver::class,
    'http_method'      => \HttpAutomock\Resolver\RequestMethodResolver::class,
    'url_hash'         => [
        'resolver' => \HttpAutomock\Resolver\RequestUrlResolver::class,
        'hashMethod' => 'xxh32',
    ],
    'url_subdirectory' => [
        'resolver' => \HttpAutomock\Resolver\RequestUrlResolver::class,
    ],
    'data_hash'        => [
        'resolver' => \HttpAutomock\Resolver\RequestResolver::class,
        'hashMethod' => 'xxh32',
    ],

],
```

With the default stack config above, a filename like `1_GET_4c147242.mock` is produced by joining
`count` (1), `http_method` (GET), and `url_hash` (4c147242) with `_`.

You can customize the stack per URL pattern. The first matching pattern wins:

```php
'filenameResolvers' => [
    '*api.example.com*' => ['http_method', 'url_subdirectory'],
    '*'                 => ['count', 'http_method', 'url_hash'],
],
```

### File Storage

Configure where and how mock files are stored. **Directory** and **Extension** control the base path and file extension
for mock files. These can be configured via CLI flags or in the published config file.

**Shared Directory** stores mocks in a shared location for all tests instead of per-test directories:

```php
Http::automock()->shared();
```

**Prettify Directory Naming** cleans up test directory names by removing closure suffixes (e.g. `_Closure_Object`) from
Pests directory naming:

```php
Http::automock()->prettifyDirectoryNaming();
```

### Headers

By default, only response bodies are saved to mock files. If your application logic depends on specific response
headers (e.g. `Content-Type`, `X-RateLimit-Remaining`), you can opt in to persisting them.

```php
Http::automock()->withHeaders(['Server']);     // Specific headers
Http::automock()->withHeaders();               // All headers
```

### Renewing / Pruning

**Renew** forces automock to re-fetch responses from the real API even when mock files already exist. The new
responses overwrite the existing mock files. This is useful when you know an external API has changed, and you want
to update your saved mocks to reflect the current behavior.

```php
Http::automock()->renew();
```

**Pruning** deletes all existing mock files for the executed test before running it. Only mock files belonging to
that specific test are removed, not mocks from other tests. This ensures you start with a clean slate, removing any
leftover mocks from requests that are no longer made by the test.

```php
Http::automock()->prune();
```

**Prevent Auto Renew** overrides and disables renewing, pruning, and validation, regardless of their individual
settings. This is useful for CI environments where you want to ensure that no real requests are made, while still
being able to pass `--automock-renew` during local development without conflicts.

```php
Http::automock()->preventAutoRenew();
```

> [!TIP]
> It would be possible to set up a CI pipeline, that renews and commits responses periodically. Consider using some sort
> of request prevention on tests where it is crucial to deny.

### Preventing Requests

By default, when no mock file exists for a request, automock makes a real HTTP call and records the response. In some
scenarios, especially CI pipelines or destructive operations, you want to guarantee that no real requests are ever
made. Automock provides two levels of protection:

**Prevent all real requests** blocks every outgoing HTTP request and throws `PreventedRequestException`. No real
requests are made, even if mock files are missing. This is the strictest mode.

```php
Http::automock()->preventRealRequests();
```

> [!NOTE]
> Avoid setting this as a project-wide default. It prevents automock from recording new mocks or renewing existing
> ones. Consider using `preventUnknownRealRequests` or no prevention whenever possible.

**Prevent only unknown requests** is a more flexible alternative. It throws `PreventedRequestException` only for
requests that don't already have a mock file on disk. Requests with existing mocks still work, including when
combined with `renew()` or `prune()` to re-fetch and update them.

```php
Http::automock()->preventUnknownRealRequests();
```

> [!NOTE]
> Like `preventRealRequests`, avoid setting this as a project-wide default, it limits automock's ability to record
> new requests.

> [!TIP]
> It is highly recommended to enable one of these options in your CI pipeline to ensure tests never make real HTTP
> calls unexpectedly.

### Skipping Requests

Exclude certain requests from being recorded or replayed. Skipped requests pass through to their normal behavior
(real HTTP call or Laravel's `Http::fake()`).

**Skip by URL pattern:**

```php
Http::automock()->skip('*example.com*', 'alias');
```

**Skip by closure:**

```php
Http::automock()->skip(fn (Request $request) => str_contains($request->url(), 'skip'), 'alias');
```

**Skip by HTTP method:**

```php
Http::automock()->skipGet();
Http::automock()->skipPost();
Http::automock()->skipPut();
Http::automock()->skipDelete();
Http::automock()->skipUnlessGet();  // Skip all except GET
```

**Clear skip filters:**

```php
Http::automock()->stopSkip();        // Clear all
Http::automock()->stopSkip('alias'); // Clear specific
```

### Validation

When enabled, automock makes real HTTP requests even when mock files already exist. Instead of replaying the saved
response, it compares the live response against the existing mock file and fails the test if they differ. The mock
files themselves are not updated. This is useful for detecting when an external API has changed its response format
or data, without permanently overwriting your saved mocks. Can be prevented explicitly by enabling prevent auto-renew.

```php
Http::automock()->validateMocks();
```

### Mock HTTP Fakes

By default, automock only records responses from real HTTP requests and ignores responses produced by Laravel's
`Http::fake()`. When this option is enabled, faked responses are also saved to mock files. This can be useful when
you want to extract inline fakes into mock files.

```php
Http::automock()->mockHttpFakes();
```

### JSON Pretty Print

When enabled (on by default), JSON response bodies are formatted with indentation in mock files. This makes diffs
more readable in version control and makes it easier to inspect saved responses manually.

```php
Http::automock()->jsonPrettyPrint();
```

## Troubleshooting

### Windows git long paths

It might be recommended / essential to use git long paths when generating long file names.
To do so, execute one of the first 2 commands, then review with the 3rd.

```shell
git config --global core.longpaths true
git config core.longpaths true
git config --list --show-origin
```

For more information see [here](https://stackoverflow.com/questions/22575662/filename-too-long-in-git-for-windows).
