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

For further examples, please refer to [this test](./tests/Feature/ExampleUsageTest.php).

```php
it('can do stuff with the api', function () {
    Http::automock();
    $response = Http::get('https://api.sampleapis.com/coffee/hot')->json();
    expect($response)->toHaveCount(20, 'There are not 20 hot coffees in the api service');
});
```

## Features & TODOs

In this list, features and TODOs can get noted that come up during usage, development and feedback.
Features are just a rough idea, whereas TODOs should get implemented at some point.

* FEATURE: Skip or retry specific responses, e.g. when a 429 error or rate limits occur. Maybe by using one of these approaches:
  * New `retryRequestsUntil` method
  * New `renewUntil` - Repeat renewing until the response contains sth.
* FEATURE: Mocks that should be reused for all test methods should be definable. Maybe by specifying a scope for specific
  requests?
* FEATURE: Configure all options like prevent, renew, ... on a requests basis
* FEATURE: Allow additional persistance of the request (for transparency reasons, not for functionality)
* FEATURE: Pipeline for name resolvers (so that one resolver may change stuff of previous resolvers)
* TODO: Null return for resolvers
* TODO: Resolving multiple Resolvers of one type in a stack resolver will only instantiate one
* TODO: Optionally remove pests closure naming (`__Closure_Object__`) in the test directory path `it_can_foo_with_data_set__dataset__foo____Closure_Object_______`
* TODO: Optionally remove pests multiple underscores (`___`) in the test directory path
* TODO: Think about an opt-in approach rather than an opt-out for auto-renew
* TODO: Think about adding more easy resolvers
  * http method (exists)
  * http domain/host
  * http domain/host without subdomain
  * http path
  * more easy hash resolver (however this is possible)
* FEATURE: Use Macroable in automock
* TODO: Possibility to provide a closure to renew, ... and other config functions

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
