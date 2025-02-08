<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Pest\TestSuite;
use function Orchestra\Testbench\package_path;
use function Pest\testDirectory;

function mockFilePath(?string $filename = null): string
{
    $testInstance = TestSuite::getInstance();
    $relativePath = str($testInstance->getFilename())
        ->remove($testInstance->rootPath.DIRECTORY_SEPARATOR.$testInstance->testPath)
        ->beforeLast('.')
        ->toString();
    $directoryPath = package_path().'/'.testDirectory().config('http-automock.directory').$relativePath.'/'.$testInstance->getDescription();
    if ($filename) {
        return $directoryPath.'/'.$filename;
    }
    return $directoryPath;
}

function deletePreviousMock(?string $filename = null): string
{
    if ($filename) {
        $mockFilePath = mockFilePath($filename);
        File::delete($mockFilePath);
        expect(File::exists($mockFilePath))->toBeFalse("File at $mockFilePath must not exist");

        return $mockFilePath;
    } else {
        $mockDirectory = mockFilePath();
        File::deleteDirectory($mockDirectory);
        expect(File::isDirectory($mockDirectory))->toBeFalse();

        return $mockDirectory;
    }
}

beforeEach(function () {
    config()->set('http-automock.filename_resolution_strategy', 'url_md5');
});

it('can automock requests', function () {
    $mockFilePath = deletePreviousMock('840ef996fc9638ba15fc85317f923d3f.mock');

    Http::automock();

    // First call -> mock created
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);
    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist");

    // Second call -> not sent
    Http::preventStrayRequests();
    Http::get('https://api.sampleapis.com/coffee/hot');
});

it('can mock when http fake is used #1', function () {
    $mockDirectory = deletePreviousMock();

    // Fake and expect the response
    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    Http::automock();
    expect(Http::get('https://test')->body())->toBe('Hello');

    // Mock file path is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
});

it('can mock when http fake is used #2', function () {
    $mockDirectory = deletePreviousMock();

    // Fake and expect the response
    Http::automock();
    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    expect(Http::get('https://test')->body())->toBe('Hello');

    // Mock file path is configured properly
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

it('cannot make requests without mocks when renew is disallowed', function () {
    $mockDirectory = deletePreviousMock();

    Http::automock()->renew(false);
    Http::get('https://api.sampleapis.com/coffee/hot');

    // Mock file path is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
})->throws(RuntimeException::class, 'Tried to send a request that has renewing disallowed');

todo('can specify the filename resolution strategy');

it('can serialize default headers', function () {
    $mockFilePath = deletePreviousMock('840ef996fc9638ba15fc85317f923d3f.mock');

    config()->set('http-automock.use_default_headers', true);
    Http::automock();
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->toContain('Content-Type: application/json; charset=utf-8')
        ->toContain('Content-Length: ')
        ->not->toContain('Connection: ')
        ->toContain('Access-Control-Allow-Origin: ')
        ->toContain('Server: ');
});

it('can serialize specific headers', function () {
    $mockFilePath = deletePreviousMock('840ef996fc9638ba15fc85317f923d3f.mock');

    Http::automock()->withHeaders(['Content-Length']);
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->not->toContain('Content-Type: application/json; charset=utf-8')
        ->toContain('Content-Length: ')
        ->not->toContain('Connection: ')
        ->not->toContain('Access-Control-Allow-Origin: ')
        ->not->toContain('Server: ');
});

it('can serialize all headers', function () {
    $mockFilePath = deletePreviousMock('840ef996fc9638ba15fc85317f923d3f.mock');

    config()->set('http-automock.use_default_headers', false);
    Http::automock();
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))->toStartWith("HTTP/1.1 200 OK\r\n\r\n");
});

it('will not serialize headers when not specified', function () {
    $mockFilePath = deletePreviousMock('840ef996fc9638ba15fc85317f923d3f.mock');

    Http::automock()->withHeaders(['Content-Length']);
    Http::get('https://api.sampleapis.com/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->not->toContain('Content-Type: application/json; charset=utf-8')
        ->toContain('Content-Length: ')
        ->not->toContain('Connection: ')
        ->not->toContain('Access-Control-Allow-Origin: ')
        ->not->toContain('Server: ');
});

it('can enable and disable pretty printing responses', function () {
    $mockFilePath = deletePreviousMock('dbfa9a6776f62af138c73e2558e8f336.mock');

    Http::fake([
        'https://test' => Http::response('{"hello":"world"}', headers: ['Content-type' => 'application/json']),
    ]);

    Http::automock()->jsonPrettyPrint(false);
    Http::get('https://test');
    expect(File::get($mockFilePath))->toContain('{"hello":"world"}'); // also checks mock file path

    File::delete($mockFilePath); // TODO Avoid this and have both files with a count

    Http::automock()->jsonPrettyPrint();
    Http::get('https://test');
    expect(File::get($mockFilePath))->toContain("{\n    \"hello\": \"world\"\n}"); // also checks mock file path
});

it('can enable and disable skipping requests', function () {
    $mockDirectory = deletePreviousMock();

    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    Http::automock()->skip('https://test');
    Http::get('https://test');
    expect(File::isDirectory($mockDirectory))->toBeFalse();

    Http::automock()->stopSkip();
    Http::get('https://test');
    expect(File::isDirectory($mockDirectory))->toBeTrue();
});

it('can skip all except get requests', function () {
    $mockDirectory = deletePreviousMock();

    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    Http::automock()->skipUnlessGet();
    Http::post('https://test');
    expect(File::isDirectory($mockDirectory))->toBeFalse();

    Http::get('https://test');
    expect(File::isDirectory($mockDirectory))->toBeTrue();
});

todo('can skip get requests', function () {
    // TODO
});

todo('can skip post requests', function () {
    // TODO
});

todo('can skip put requests', function () {
    // TODO
});

todo('can skip delete requests', function () {
    // TODO
});
