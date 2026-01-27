# Laravel Http Automock

[![Run Tests](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml/badge.svg)](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml)

Laravel package for tests to automatically mock HTTP requests using laravels
[HTTP client](https://laravel.com/docs/11.x/http-client) and [Pest](https://pestphp.com/).

## Prerequisites

This package currently only works using [Pest](https://pestphp.com/) and laravels
[HTTP client](https://laravel.com/docs/11.x/http-client).

## Installation

```shell
composer require michiruf/laravel-http-automock --dev
```

Publish the config:

```shell
php artisan vendor:publish --tag="http-automock-config"
```

## Usage

To enable automock, call `Http::automock();` inside your tests before executing the requests you want to send to
save responses automatically and use them in the next tests runs.

For further examples, please refer to [this test](./tests/Feature/ExampleUsageTest.php) or detailed sections of this
document.

```php
it('can do stuff with the api', function () {
    Http::automock();
    $response = Http::get('https://api.sampleapis.com/coffee/hot')->json();
    expect($response)->toHaveCount(20, 'There are not 20 hot coffees in the api service');
});
```

### General configuration options

Each feature can be configured via three methods (in order of precedence):

1. **Fluent API** - `Http::automock()->someFeature()` or `Http::configureAutomock()->someFeature()`
2. **CLI** - `./vendor/bin/pest --automock-option`
3. **Config** - `config/http-automock.php` if you have published the configuration

Note that calling `Http::automock()` will also enable automock for this test.

### Enable / Disable

Temporarily disable automock without removing the call.

| Method | Usage                                         |
|--------|-----------------------------------------------|
| Fluent | `Http::automock()->disable()` or `->enable()` |
| CLI    | `--automock-enabled`                          |
| Config | `'enabled' => true`                           |

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

Clean up test directory names by removing closure suffixes.

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

Disable renewing, pruning, and validation (useful for CI).

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
> reproducible behavior, it might be the right choice for a specific test case, but when by setting this as a project
> default, automock will lack some useful features.
> automock will lack features to renew requests.
> Consider using `preventUnknownRealRequests` instead whenever possible.

**Prevent only unknown requests:**

| Method | Usage                                            |
|--------|--------------------------------------------------|
| Fluent | `Http::automock()->preventUnknownRealRequests()` |
| CLI    | `--automock-prevent-unknown-real-requests`       |
| Config | `'prevent_unknown_real_requests' => false`       |

> [!NOTE]  
> It is highly recommended not to set this as a default on a project level. When working with APIs that do have a
> reproducible behavior, it might be the right choice for a specific test case, but when by setting this as a project
> default, automock will lack some useful features.

Throws `PreventedRequestException` only for requests without an existing mock. Useful with `renew()` to refresh existing
mocks while preventing new ones:

```php
Http::automock()->preventUnknownRealRequests()->renew();
```

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

## Develpopment: Features & TODOs

In this list, features and TODOs can get noted that come up during usage, development and feedback.
Features are just a rough idea, whereas TODOs should get implemented at some point.

* FEATURE: Skip or retry specific responses, e.g. when a 429 error or rate limits occur. Maybe by using one of these approaches:
  * New `retryRequestsUntil` method
  * New `renewUntil` - Repeat renewing until the response contains sth.
* FEATURE: Mocks that should be reused for all test methods should be definable. Maybe by specifying a scope for specific
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
