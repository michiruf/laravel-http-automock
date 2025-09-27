<?php

use HttpAutomock\HttpAutomock;

beforeEach(function () {
    config()->set('http-automock.enabled', false); // set default to be config independent
});

it('can use the mixin', function () {
    $automock = Http::automock();
    expect($automock)->toBeInstanceOf(HttpAutomock::class)
        ->enabled()->toBeTrue();
});

it('can enable and disable automock and check whether automock is enabled', function () {
    expect(Http::automockEnabled())->toBeFalse();

    Http::automock();
    expect(Http::automockEnabled())->toBeTrue();

    Http::noAutomock();
    expect(Http::automockEnabled())->toBeFalse();
});

it('can configure automock', function () {
    $automock = Http::configureAutomock();
    expect($automock)->toBeInstanceOf(HttpAutomock::class)
        ->enabled()->toBeFalse();
});
