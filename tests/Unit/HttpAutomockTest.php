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

it('can mock when http fake is used #1', function () {
    // Preconditions
    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_can_mock_when_http_fake_is_used__1/');
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

it('can mock when http fake is used #2', function () {
    // Preconditions
    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_can_mock_when_http_fake_is_used__2/');
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
    Http::automock()->renew();
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

//it('cannot make requests without mocks when renew is disallowed', function () {
//    // Precondition
//    $mockDirectory = mockFilePath('/Unit/HttpAutomockTest/it_cannot_make_request_without_mocks_when_renew_is_disallowed/');
//    File::deleteDirectory($mockDirectory);
//    expect(File::isDirectory($mockDirectory))->toBeFalse();
//
//    Http::automock()->renew(false);
//    Http::get('https://api.sampleapis.com/coffee/hot');
//
//    // Precondition is configured properly
//    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
//})->throws(RuntimeException::class, 'Tried to send a request that has renewing disallowed');

it('can enable pretty printing responses', function () {
    // Preconditions
    $mockPath = mockFilePath('/Unit/HttpAutomockTest/it_can_enable_pretty_printing_responses/dbfa9a6776f62af138c73e2558e8f336.mock');
    File::delete($mockPath);
    expect(File::exists($mockPath))->toBeFalse("File at $mockPath must not exist");

    Http::automock()->jsonPrettyPrint();
    Http::fake([
        'https://test' => Http::response('{"hello":"world"}', headers: ['Content-Type' => 'application/json']),
    ]);
    Http::get('https://test');
    expect(File::get($mockPath))->toBe("{\n    \"hello\": \"world\"\n}"); // also checks precondition
});

it('can disable pretty printing responses', function () {
    // Preconditions
    $mockPath = mockFilePath('/Unit/HttpAutomockTest/it_can_disable_pretty_printing_responses/dbfa9a6776f62af138c73e2558e8f336.mock');
    File::delete($mockPath);
    expect(File::exists($mockPath))->toBeFalse("File at $mockPath must not exist");

    Http::automock()->jsonPrettyPrint(false);
    Http::fake([
        'https://test' => Http::response('{"hello":"world"}', headers: ['Content-type' => 'application/json']),
    ]);
    Http::get('https://test');
    expect(File::get($mockPath))->toBe('{"hello":"world"}'); // also checks precondition
});

it('can enable and disable pretty printing responses', function () {
    // TODO Decide whit tests to keep for disabling pretty print

    // Preconditions
    $mockPath = mockFilePath('/Unit/HttpAutomockTest/it_can_enable_and_disable_pretty_printing_responses/dbfa9a6776f62af138c73e2558e8f336.mock');
    File::delete($mockPath);
    expect(File::exists($mockPath))->toBeFalse("File at $mockPath must not exist");

    Http::fake([
        'https://test' => Http::response('{"hello":"world"}', headers: ['Content-type' => 'application/json']),
    ]);

    Http::automock()->jsonPrettyPrint(false);
    Http::get('https://test');
    expect(File::get($mockPath))->toBe('{"hello":"world"}'); // also checks precondition

    File::delete($mockPath);

    Http::automock()->jsonPrettyPrint();
    Http::get('https://test');
    expect(File::get($mockPath))->toBe("{\n    \"hello\": \"world\"\n}"); // also checks precondition
});
