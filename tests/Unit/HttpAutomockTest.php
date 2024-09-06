<?php

use Illuminate\Support\Facades\Http;

use function Orchestra\Testbench\package_path;
use function Pest\testDirectory;

function mockFilePath(string $path): string
{
    return package_path().'/'.testDirectory().config('http-automock.directory').$path;
}

beforeEach(function () {
    config()->set('http-automock.filename_resolution_strategy', 'url_md5');
});

it('can automock requests', function () {
    // Preconditions
    $mockPath = mockFilePath('/Unit/HttpAutomockTest/it_can_automock_requests/840ef996fc9638ba15fc85317f923d3f.mock');
    File::delete($mockPath);
    expect(File::exists($mockPath))->toBeFalse("File at $mockPath must not exist");

    Http::automock();

    // First call -> mock created
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);
    expect(File::exists($mockPath))->toBeTrue("File at $mockPath must exist");

    // Second call -> not sent
    Http::preventStrayRequests();
    Http::get('https://api.sampleapis.com/coffee/hot');
});

it('can still work when http fake is used #1', function () {
    // Preconditions
    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_can_still_work_when_http_fake_is_used__1/');
    File::deleteDirectory($mockDirectory);
    expect(File::isDirectory($mockDirectory))->toBeFalse();

    // Fake and expect the response
    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    Http::automock();
    expect(Http::get('https://test')->body())->toBe('Hello');

    // Precondition is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
});

it('can still work when http fake is used #2', function () {
    // Preconditions
    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_can_still_work_when_http_fake_is_used__2/');
    File::deleteDirectory($mockDirectory);
    expect(File::isDirectory($mockDirectory))->toBeFalse();

    // Fake and expect the response
    Http::automock();
    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    expect(Http::get('https://test')->body())->toBe('Hello');

    // Precondition is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
});

it('can force renew responses', function () {
    Http::automock()->forceRenew();
    Http::fake([
        'https://test' => Http::sequence([
            Http::response('Hello'),
            Http::response('There'),
        ]),
    ]);
    expect()
        ->and(Http::get('https://test')->body())->toBe('Hello')
        ->and(Http::get('https://test')->body())->toBe('There');
});

it('cannot make requests without mocks when renew is disallowed', function () {
    // Precondition
    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_cannot_make_request_without_mocks_when_renew_is_disallowed/');
    File::deleteDirectory($mockDirectory);
    expect(File::isDirectory($mockDirectory))->toBeFalse();

    Http::automock()->disallowRenew();
    Http::get('https://api.sampleapis.com/coffee/hot');

    // Precondition is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
})->throws(RuntimeException::class, 'Tried to send a request that has renewing disallowed');
