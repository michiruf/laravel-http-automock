# Laravel Http Automock

[![Run Tests](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml/badge.svg)](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml)

Automatically record and replay HTTP responses in your Laravel tests. On the first test run, real HTTP requests are
made and the responses are saved to disk. On subsequent runs, the saved responses are used instead — no real requests
are made. This makes your tests faster, deterministic, and independent of external services.

Requires PHP 8.2+, Laravel 10–12, and [Pest](https://pestphp.com/).
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

For more examples, see the [example test](./tests/Feature/ExampleUsageTest.php).

### How It Works

1. You call `Http::automock()` in a test — this registers the recording/replaying handlers
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
requests in tests ensures your application actually works against live data, but the execution time is high — and
maintaining both faked and real test setups creates significant overhead.

Laravel Http Automock removes that burden. On the first run, your tests hit the real APIs and responses are saved
automatically. Every subsequent run replays those responses instantly. You get the confidence of real data without
the cost of real requests.

Since mock files live in your repository, git naturally picks up changes in external API responses which makes it easy
to notice when a service changed its behavior while keeping a safe copy of the data.

## Configuration

Each feature can be configured via three methods (in order of precedence):

1. **Fluent API** — `Http::automock()->someFeature()` or `Http::configureAutomock()->someFeature()`
2. **CLI** — `./vendor/bin/pest --automock-option`
3. **Config** — `config/http-automock.php` (if published)

> [!NOTE]
> `Http::automock()` both enables automock and returns the instance for fluent configuration.
> `Http::configureAutomock()` returns the instance without enabling automock — useful for setting options separately.

### Enable / Disable

Automock is not active by default. It must be explicitly enabled per-test by calling `Http::automock()`.
To temporarily disable automock within a test without removing the call, use `disable()`:

| Method | Usage                                              |
|--------|----------------------------------------------------|
| Fluent | `Http::automock()->disable()` or `->enable()`      |
| Fluent | `Http::noAutomock()` (equivalent to `->disable()`) |
| CLI    | `--automock-enabled`                               |

### File Storage

Configure where and how mock files are stored.

**Directory**

| Method | Usage                                 |
|--------|---------------------------------------|
| CLI    | `--automock-directory=.pest/automock` |
| Config | `'directory' => '.pest/automock'`     |

**Extension**

| Method | Usage                        |
|--------|------------------------------|
| CLI    | `--automock-extension=.json` |
| Config | `'extension' => '.mock'`     |

**Shared Directory**

Store mocks in a shared location for all tests instead of per-test directories.

| Method | Usage                                                                |
|--------|----------------------------------------------------------------------|
| Fluent | `Http::automock()->shared()`                                         |
| CLI    | `--automock-shared`, `--automock-shared-directory=...`               |
| Config | `'shared' => false`, `'shared_directory' => '.pest/automock/Shared'` |

**Prettify Directory Naming**

Clean up test directory names by removing closure suffixes (e.g. `_Closure_Object`).

| Method | Usage                                         |
|--------|-----------------------------------------------|
| Fluent | `Http::automock()->prettifyDirectoryNaming()` |
| CLI    | `--automock-prettify-directory-naming`        |
| Config | `'prettify_directory_naming' => false`        |

### File Name Resolvers

Customize how mock filenames are generated. Default: `1_GET_4c147242.mock`

| Method | Usage                                            |
|--------|--------------------------------------------------|
| Fluent | `Http::automock()->resolveFileNameUsing(...)`    |
| CLI    | `--automock-file-name-resolver=url_subdirectory` |
| Config | `'default_filename_resolver' => 'stack'`         |

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

Available resolvers in config: `stack`, `count`, `http_method`, `url_hash`, `url_subdirectory`, `data_hash`

### Headers

Control which response headers are persisted in mock files. Default: none.

| Method | Usage                                                              |
|--------|--------------------------------------------------------------------|
| Fluent | `Http::automock()->withHeaders()`                                  |
| CLI    | `--automock-headers=Content-Type,Server`                           |
| Config | `'use_default_headers' => false`, `'default_header_list' => [...]` |

```php
Http::automock()->withHeaders(['Server']);     // Specific headers
Http::automock()->withHeaders();               // All headers
```

### Renewing / Pruning

Force re-fetching responses even when mocks already exist.

| Method | Usage                       |
|--------|-----------------------------|
| Fluent | `Http::automock()->renew()` |
| CLI    | `--automock-renew`          |
| Config | `'renew' => false`          |

**Prevent Auto Renew**

When enabled, `preventAutoRenew()` **overrides** and disables renewing, pruning, and validation — regardless of
their individual settings. This is useful for CI environments where you want to ensure that no real requests are
made, while still being able to pass `--automock-renew` during local development without conflicts.

| Method | Usage                                  |
|--------|----------------------------------------|
| Fluent | `Http::automock()->preventAutoRenew()` |
| CLI    | `--automock-prevent-auto-renew`        |
| Config | `'prevent_auto_renew' => false`        |

**Pruning**

Delete old mock files before running the test.

| Method | Usage                       |
|--------|-----------------------------|
| Fluent | `Http::automock()->prune()` |
| CLI    | `--automock-prune`          |
| Config | `'prune' => false`          |

### Preventing Requests

Ensure tests don't accidentally make real HTTP calls.

**Prevent all real requests:**

| Method | Usage                                     |
|--------|-------------------------------------------|
| Fluent | `Http::automock()->preventRealRequests()` |
| CLI    | `--automock-prevent-real-requests`        |
| Config | `'prevent_real_requests' => false`        |

Throws `PreventedRequestException` for any real request.

> [!NOTE]
> It is highly recommended not to set this as a default on a project level. When working with APIs that do have a
> reproducible behavior, it might be the right choice for a specific test case, but when setting this as a project
> default, automock will lack features to renew requests.
> Consider using `preventUnknownRealRequests` instead whenever possible.

**Prevent only unknown requests:**

| Method | Usage                                            |
|--------|--------------------------------------------------|
| Fluent | `Http::automock()->preventUnknownRealRequests()` |
| CLI    | `--automock-prevent-unknown-real-requests`       |
| Config | `'prevent_unknown_real_requests' => false`       |

Throws `PreventedRequestException` only for requests without an existing mock. Useful with `renew()` to refresh existing
mocks while preventing new ones:

```php
Http::automock()->preventUnknownRealRequests()->renew();
```

> [!NOTE]
> It is highly recommended not to set this as a default on a project level. When working with APIs that do have a
> reproducible behavior, it might be the right choice for a specific test case, but when setting this as a project
> default, automock will lack some useful features.

### Skipping Requests

Skip automocking for specific requests.

| Method | Usage                                    |
|--------|------------------------------------------|
| Fluent | `Http::automock()->skip(...)`            |
| CLI    | `--automock-url-filters=*example.com*`   |
| Config | `'url_filters' => []`, `'filters' => []` |

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

Assert that responses match existing mock files.

| Method | Usage                               |
|--------|-------------------------------------|
| Fluent | `Http::automock()->validateMocks()` |
| CLI    | `--automock-validate-mocks`         |
| Config | `'validate_mocks' => false`         |

### Mock HTTP Fakes

Also save responses from Laravel's `Http::fake()`.

| Method | Usage                               |
|--------|-------------------------------------|
| Fluent | `Http::automock()->mockHttpFakes()` |
| CLI    | `--automock-mock-http-fakes`        |
| Config | `'mock_http_fakes' => false`        |

### JSON Pretty Print

Pretty print JSON responses in mock files.

| Method | Usage                                 |
|--------|---------------------------------------|
| Fluent | `Http::automock()->jsonPrettyPrint()` |
| CLI    | `--automock-json-pretty-print`        |
| Config | `'json_pretty_print' => true`         |

## Development: Features & TODOs

In this list, features and TODOs can get noted that come up during usage, development and feedback.
Features are just a rough idea, whereas TODOs should get implemented at some point.

* FEATURE: Skip or retry specific responses, e.g. when a 429 error or rate limits occur. Maybe by using one of these
  approaches:
    * New `retryRequestsUntil` method
    * New `renewUntil` - Repeat renewing until the response contains sth.
* FEATURE: Mocks that should be reused for all test methods should be definable. Maybe by specifying a scope for
  specific
  requests?
* FEATURE: Configure all options like prevent, renew, ... on a requests basis
* FEATURE: Allow additional persistence of the request (for transparency reasons, not for functionality)
* FEATURE: Pipeline for name resolvers (so that one resolver may change stuff of previous resolvers)
* TODO: Null return for resolvers
* TODO: Resolving multiple Resolvers of one type in a stack resolver will only instantiate one
* TODO: Think about an opt-in approach rather than an opt-out for auto-renew
* TODO: Think about adding more easy resolvers
    * http method (exists)
    * http domain/host
    * http domain/host without subdomain
    * http path
    * more easy hash resolver (however this is possible)
* TODO: Think about versioning file name resolvers, because it is crucial if they change inside a project
* FEATURE: Use Macroable in automock
* TODO: Possibility to provide a closure to renew, ... and other config functions
* TODO: Unit tests

## Troubleshooting

### Windows git long paths

It might be recommended / essential to use git long paths when generating long file names.
To do so, execute

```shell
git config --global core.longpaths true
```

if you want to enable long paths in general or

```shell
git config core.longpaths true
```

to enable long paths in the project.

Validate the setting via

```shell
git config --list --show-origin
```

For more information see [here](https://stackoverflow.com/questions/22575662/filename-too-long-in-git-for-windows).
