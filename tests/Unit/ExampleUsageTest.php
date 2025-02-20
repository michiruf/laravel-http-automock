<?php

use HttpAutomock\Resolver\RequestUrlResolver;
use Illuminate\Http\Client\Request;

/**
 * In all these example tests, please view the files that are specified in the File::exists assertion.
 */

it('can do stuff with the api', function () {
    Http::automock();
    $response = Http::get('http://localhost:9337/coffee/hot')->json();
    expect($response)->toHaveCount(20, 'There are not 20 hot coffees in the api service')
        ->and(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_do_stuff_with_the_api/1_GET_4c147242.mock'))->toBeTrue();
});

it('can specify file names via file resolver', function () {
    Http::automock()->resolveFileNameUsing(new RequestUrlResolver(port: false, removeSlashes: false));
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_specify_file_names_via_file_resolver/localhost/coffee/hot.mock'))->toBeTrue();
});

it('can specify file names via closure', function () {
    Http::automock()->resolveFileNameUsing(fn (Request $request, bool $forWriting) => "TEST-{$request->method()}");
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_specify_file_names_via_closure/TEST-GET.mock'))->toBeTrue();
});

it('can persist some headers', function () {
    Http::automock()->withHeaders(['Server']);
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_persist_some_headers/1_GET_4c147242.mock'))->toBeTrue();
});

it('can persist default headers', function () {
    config()->set('http-automock.use_default_headers', true);
    Http::automock();
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_persist_default_headers/1_GET_4c147242.mock'))->toBeTrue();
});

it('can persist all headers', function () {
    Http::automock()->withHeaders();
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_can_persist_all_headers/1_GET_4c147242.mock'))->toBeTrue();
});

it('can force renew responses', function () {
    $now = now();
    Http::automock()->renew();
    Http::get('http://localhost:9337/coffee/hot');
    $path = 'tests/.pest/automock/Unit/ExampleUsageTest/it_can_force_renew_responses/1_GET_4c147242.mock';
    expect(File::exists($path))->toBeTrue()
        ->and(File::lastModified($path))->toBeGreaterThanOrEqual($now->getTimestamp());
});

it('can disallow renewing responses', function () {
    Http::automock()->renew(false);
    Http::get('http://localhost:9337/coffee/hot');
})->throws(RuntimeException::class, 'Tried to send a request that has renewing disallowed');

it('can disable automock entirely', function () {
    Http::automock()->disable();
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::isDirectory('tests/.pest/automock/Unit/ExampleUsageTest/it_can_disable_automock_entirely'))->toBeFalse();
});

it('can skip automock for all requests', function () {
    Http::automock()->skip(fn (Request $request) => true, 'skip all');
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::isDirectory('tests/.pest/automock/Unit/ExampleUsageTest/it_can_skip_all_requests'))->toBeFalse();
});

it('is possible to set the extension via config', function () {
    config()->set('http-automock.extension', '.json');
    Http::automock();
    Http::get('http://localhost:9337/coffee/hot');
    expect(File::exists('tests/.pest/automock/Unit/ExampleUsageTest/it_is_possible_to_set_the_extension_via_config/1_GET_4c147242.json'))->toBeTrue();
});
