# Laravel Http Automock

[![Run Tests](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml/badge.svg)](https://github.com/michiruf/laravel-http-automock/actions/workflows/run-tests.yml)

## Prerequisites

Pest!
TODO

## Installation

TODO

## TODOs

Strategy:
    'url_method_count' => filename: api.example.org__get__1
                                 [url] [method] [count]

## Features that could get implemented

* Skip specific responses, maybe by one of these approaches:
  * `retryRequestsUntil` method
  * `renewUntil` - Repeat renewing until the response contains sth.
    e.g. a 429 error should get repeated
* Clear all auto mocks invoking the test command with option `--prune`
* Update all auto mocks invoking the test command with option `--update`
* Mocks that should be reused for all test methods should be definable.
  Use case: 
* Only automock requests that are a real request
  ```php
  // 'Real Request'
  ! empty($event->response->handlerStats()),
  ```
