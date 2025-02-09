# Laravel Http Automock

[![Run Tests](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml/badge.svg)](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml)

Laravel package for tests to automatically mock HTTP requests using laravels
[HTTP client](https://laravel.com/docs/11.x/http-client) and [Pest](https://pestphp.com/).

## Prerequisites

This package currently only works using [Pest](https://pestphp.com/) and laravels
[HTTP client](https://laravel.com/docs/11.x/http-client).

## Installation

```shell
composer require michiruf/laravel-http-automock
```

Publish the config:

```shell
php artisan vendor:publish --tag="http-automock-config"
```

## Usage

To enable automock, call `Http::automock();` inside your tests before executing the requests you want to send to
save responses automatically and use them in the next tests runs.

For further examples, please refer to [this test](./tests/Unit/ExampleUsageTest.php).

```php
it('can do stuff with the api', function () {
    Http::automock();
    $response = Http::get('https://api.sampleapis.com/coffee/hot')->json();
    expect($response)->toHaveCount(20, 'There are not 20 hot coffees in the api service');
});
```

## Features that could get implemented

* Skip or retry specific responses, e.g. when a 429 error or rate limits occur. Maybe by using one of these approaches:
    * New `retryRequestsUntil` method
    * New `renewUntil` - Repeat renewing until the response contains sth.
* Clear all automocks invoking the test command with option `--prune` or `--prune-automocks`
* Update all automocks invoking the test command with option `--update` or `--update-automocks`
* Mocks that should be reused for all test methods should be definable. Maybe by specifying a scope for specific
  requests?
* Only automock requests that are a real request
  ```php
  // 'Real Request'
  ! empty($event->response->handlerStats()),
  ```
