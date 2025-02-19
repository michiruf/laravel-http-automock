<?php

use HttpAutomock\Resolver\CountResolver;
use HttpAutomock\Resolver\RequestMethodResolver;
use HttpAutomock\Resolver\RequestUrlResolver;
use HttpAutomock\Resolver\Resolver;
use HttpAutomock\Resolver\StackResolver;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Event;
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

function isRealResponse(Response $response): bool
{
    return ! empty($response->handlerStats());
}

beforeEach(function () {
    config()->set('http-automock.filename_resolvers', [
        'stack' => [
            'resolver' => StackResolver::class,
            'filenameResolvers' => [
                '*' => ['count', 'http_method', 'url_md5']
            ],
            'delimiter' => '_',
        ],
        'count' => CountResolver::class,
        'http_method' => RequestMethodResolver::class,
        'url_md5' => [
            'resolver' => RequestUrlResolver::class,
            'hashMethod' => 'md5',
        ],
    ]);
    config()->set('http-automock.default_filename_resolver', 'url_md5');
});

it('can automock requests', function () {
    $mockFilePath = deletePreviousMock('b50e3de4f046e52a54288dba34e7b10a.mock');

    Http::automock();

    // First call -> mock created
    // For any reason, we get the handler stats only when getting the response via event
    Event::listen(ResponseReceived::class, fn (ResponseReceived $event) => expect(isRealResponse($event->response))->toBeTrue());
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(1);
    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist");
    Event::forget(ResponseReceived::class);

    // Second call -> not sent
    Event::listen(ResponseReceived::class, fn (ResponseReceived $event) => expect(isRealResponse($event->response))->toBeFalse());
    Http::preventStrayRequests();
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(2);
    Event::forget(ResponseReceived::class);
});

it('can mock when http fake is used #1', function () {
    $mockDirectory = deletePreviousMock();

    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    Http::automock();
    expect(Http::get('https://test')->body())->toBe('Hello')
        ->and(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
});

it('can mock when http fake is used #2', function () {
    $mockDirectory = deletePreviousMock();

    Http::automock();
    Http::preventStrayRequests();
    Http::fake([
        'https://test' => Http::response('Hello'),
    ]);
    expect(Http::get('https://test')->body())->toBe('Hello')
        ->and(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
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
    Http::get('http://localhost:9337/coffee/hot');

    // Mock file path is configured properly
    expect(File::isDirectory($mockDirectory))->toBeTrue('Set up wrong directory in test');
})->throws(RuntimeException::class, 'Tried to send a request that has renewing disallowed');

it('can specify filename resolution', function () {
    Http::fake([
        'https://test' => Http::response(),
    ]);

    // Specify explicitly for this instance
    deletePreviousMock();
    Http::automock()->resolveFileNameUsingResolverAndArgs(CountResolver::class);
    Http::get('https://test');
    expect(File::exists(mockFilePath('1.mock')))->toBeTrue();

    // Specify resolver from the config
    deletePreviousMock();
    Http::automock()->resolveFileNameUsing('stack');
    Http::get('https://test');
    expect(File::exists(mockFilePath('1_GET_dbfa9a6776f62af138c73e2558e8f336.mock')))->toBeTrue();

    // Specify defaults using the config and reset to using the config
    deletePreviousMock();
    config()->set('http-automock.filename_resolvers', [
        'custom' => [
            'resolver' => StackResolver::class,
            'filenameResolvers' => [
                '*' => [
                    'count',
                    fn (Request $request) => $request->method(),
                    new Resolver(fn ($request, $forWriting, $directory) => 'TEST')
                ]
            ],
            'delimiter' => '_',
        ],
        'count' => CountResolver::class,
    ]);
    config()->set('http-automock.default_filename_resolver', 'custom');
    Http::automock()->resolveFileNameUsing(null);
    Http::get('https://test');
    expect(File::exists(mockFilePath('1_GET_TEST.mock')))->toBeTrue();
});

it('can specify closure filename resolutions', function () {
    Http::fake([
        'https://test' => Http::response(),
    ]);

    deletePreviousMock();
    Http::automock()->resolveFileNameUsing(function (Request $request, bool $forWriting) {
        return $request->method();
    });
    Http::get('https://test');
    expect(File::exists(mockFilePath('GET.mock')))->toBeTrue();
});

it('can serialize default headers', function () {
    $mockFilePath = deletePreviousMock('b50e3de4f046e52a54288dba34e7b10a.mock');

    config()->set('http-automock.use_default_headers', true);
    Http::automock();
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->toContain('Content-Type: application/json; charset=utf-8')
        ->not->toContain('Connection: ')
        ->toContain('Access-Control-Allow-Origin: ')
        ->toContain('Server: ');
});

it('can serialize specific headers', function () {
    $mockFilePath = deletePreviousMock('b50e3de4f046e52a54288dba34e7b10a.mock');

    Http::automock()->withHeaders(['Content-Length']);
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->not->toContain('Content-Type: application/json; charset=utf-8')
        ->not->toContain('Connection: ')
        ->not->toContain('Access-Control-Allow-Origin: ')
        ->not->toContain('Server: ');
});

it('can serialize all headers', function () {
    $headers = [
        'Date' => 'Sun, 09 Feb 2025 10:47:04 GMT',
        'Content-Type' => 'application/json; charset=utf-8',
        'Content-Length' => '8671',
        'Connection' => 'keep-alive',
        'X-Powered-By' => 'Express',
        'Access-Control-Allow-Origin' => '*',
        'X-RateLimit-Limit' => '5000',
        'X-RateLimit-Remaining' => '4953',
        'X-RateLimit-Reset' => '1739098622',
        'X-Content-Type-Options' => 'nosniff',
        'ETag' => 'W/"21df-Qjes7uaeQItDszmliRPvMUXoGIs"',
        'cf-cache-status' => 'DYNAMIC',
        'Report-To' => '{"endpoints":[{"url":"https:\/\/a.nel.cloudflare.com\/report\/v4?s=1g0gOmjcYNjZvoVMD9WtNE4vi%2Fg83S%2FzdE4rpRQgQBnrpLmrSfk1gLigG%2BFDDmAyjwap5DiTro3jeoZmlZ67pbW7kjb2kmg1o6Y6tKEV3jwd5qxat07%2B5u716IByS%2B2Cmq1jyPo%3D"}],"group":"cf-nel","max_age":604800}',
        'NEL' => '{"success_fraction":0,"report_to":"cf-nel","max_age":604800}',
        'Server' => 'cloudflare',
        'CF-RAY' => '90f3477a0f53e5f3-IAD',
        'alt-svc' => 'h3=":443"; ma=86400',
        'server-timing' => 'cfL4;desc="?proto=TCP&rtt=135730&min_rtt=135550&rtt_var=50960&sent=4&recv=5&lost=0&retrans=0&sent_bytes=2847&recv_bytes=699&delivery_rate=21423&cwnd=58&unsent_bytes=0&cid=081659bd52451ddd&ts=249&x=0"',
    ];
    Http::fake([
        'http://localhost:9337/coffee/hot' => Http::response('Hello', 201, $headers)
    ]);

    $mockFilePath = deletePreviousMock('b50e3de4f046e52a54288dba34e7b10a.mock');

    config()->set('http-automock.use_default_headers', false);
    Http::automock()->withHeaders();
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(1);

    $mockContent = collect($headers)
        ->map(fn ($value, $header) => "$header: $value")
        ->prepend("HTTP/1.1 201 Created")
        ->add("")
        ->add("Hello")
        ->join("\r\n");
    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))->toBe($mockContent);
});

it('will not serialize headers when not specified', function () {
    $mockFilePath = deletePreviousMock('b50e3de4f046e52a54288dba34e7b10a.mock');

    Http::automock()->withHeaders(['Content-Length']);
    Http::get('http://localhost:9337/coffee/hot');
    Http::assertSentCount(1);

    expect(File::exists($mockFilePath))->toBeTrue("File at $mockFilePath must exist")
        ->and(File::get($mockFilePath))
        ->toContain('HTTP/1.1 200 OK')
        ->not->toContain('Content-Type: application/json; charset=utf-8')
        ->not->toContain('Connection: ')
        ->not->toContain('Access-Control-Allow-Origin: ')
        ->not->toContain('Server: ');
});

it('can enable and disable pretty printing responses', function () {
    Http::fake([
        'https://test' => Http::response('{"hello":"world"}', headers: ['Content-type' => 'application/json']),
    ]);
    Http::automock()
        ->resolveFileNameUsingResolverAndArgs(CountResolver::class)
        ->renew();

    Http::automock()->jsonPrettyPrint(false);
    Http::get('https://test');
    expect(File::get(mockFilePath('1.mock')))->toContain('{"hello":"world"}');

    Http::automock()->jsonPrettyPrint();
    Http::get('https://test');
    expect(File::get(mockFilePath('2.mock')))->toContain("{\n    \"hello\": \"world\"\n}");
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
