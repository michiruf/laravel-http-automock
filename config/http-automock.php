<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directory
    |--------------------------------------------------------------------------
    |
    | The directory where the automatically created mocks should be stored in.
    |
    */
    'directory' => '.pest/automock',

    /*
    |--------------------------------------------------------------------------
    | Shared mocks
    |--------------------------------------------------------------------------
    |
    | When 'shared' is enabled, mocks will be stored in a shared directory
    | that can be used across multiple tests.
    |
    | 'shared_directory' configures the directory where shared mocks are
    | stored.
    |
    */
    'shared' => false,
    'shared_directory' => '.pest/automock/Shared',

    /*
    |--------------------------------------------------------------------------
    | Prettify directory naming
    |--------------------------------------------------------------------------
    |
    | When enabled, the directory names for mocks will be prettified to be
    | more human-readable.
    |
    */
    'prettify_directory_naming' => false,

    /*
    |--------------------------------------------------------------------------
    | Mock extension
    |--------------------------------------------------------------------------
    |
    | The file extensions generated mocks should be appended with.
    | Typically you may want to set this to json to have your IDE recognize
    | the format.
    |
    */
    'extension' => '.mock',

    /*
    |--------------------------------------------------------------------------
    | Filename Resolution
    |--------------------------------------------------------------------------
    |
    | Configure how filenames for automocks should get generated.
    |
    | 'default_filename_resolver' configures the resolver to be used when no
    | other resolver is specified via `Http::automock()->resolveFileNameUsing`
    | or `Http::automock()->resolveFileNameUsingResolverAndArgs`.
    |
    | 'filename_resolvers' configures a list of available resolvers and their
    | parameters. Therefore, the values specified inside a resolver, get passed
    | to the resolver as arguments when instantiating. The key 'resolver'
    | specifies the class itself.
    | Inside these resolvers, additional dependencies can get resolved from the
    | container without further configuration.
    |
    */
    'default_filename_resolver' => 'stack',
    'filename_resolvers' => [

        'stack' => [
            'resolver' => \HttpAutomock\Resolver\StackResolver::class,
            'filenameResolvers' => [
                '*' => ['count', 'http_method', 'url_hash'],
            ],
            'delimiter' => '_',
        ],

        'count' => \HttpAutomock\Resolver\CountResolver::class,

        'http_method' => \HttpAutomock\Resolver\RequestMethodResolver::class,

        'url_hash' => [
            'resolver' => \HttpAutomock\Resolver\RequestUrlResolver::class,
            'hashMethod' => 'xxh32',
        ],

        'url_subdirectory' => [
            'resolver' => \HttpAutomock\Resolver\RequestUrlResolver::class,
        ],

        'data_hash' => [
            'resolver' => \HttpAutomock\Resolver\RequestResolver::class,
            'hashMethod' => 'xxh32',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Prevent real requests
    |--------------------------------------------------------------------------
    |
    | When 'prevent_real_requests' is enabled, all real HTTP requests will be
    | prevented and an exception will be thrown instead.
    |
    | When 'prevent_unknown_real_requests' is enabled, only requests that do
    | not have an existing mock will be prevented.
    |
    */
    'prevent_real_requests' => false,
    'prevent_unknown_real_requests' => false,

    /*
    |--------------------------------------------------------------------------
    | Renew mocks
    |--------------------------------------------------------------------------
    |
    | When 'renew' is enabled, existing mocks will be renewed by making real
    | HTTP requests again.
    |
    | When 'prevent_auto_renew' is enabled, automatic renewing of mocks is
    | prevented. This also prevents pruning and validation.
    |
    */
    'renew' => false,
    'prevent_auto_renew' => false,

    /*
    |--------------------------------------------------------------------------
    | Prune mocks
    |--------------------------------------------------------------------------
    |
    | When enabled, mocks that are no longer used will be pruned (deleted).
    |
    */
    'prune' => false,

    /*
    |--------------------------------------------------------------------------
    | Validate mocks
    |--------------------------------------------------------------------------
    |
    | When enabled, existing mocks will be validated against real HTTP
    | responses to ensure they are still up to date.
    |
    */
    'validate_mocks' => false,

    /*
    |--------------------------------------------------------------------------
    | Mock HTTP fakes
    |--------------------------------------------------------------------------
    |
    | When enabled, HTTP fakes (responses registered via Http::fake()) will
    | also be mocked by automock.
    |
    */
    'mock_http_fakes' => false,

    /*
    |--------------------------------------------------------------------------
    | Serialize headers & default header list
    |--------------------------------------------------------------------------
    |
    | If 'use_default_headers' ist set to true, unless other specified, the
    | 'default_header_list' will be passed to the request serializer if not
    | specified more specifically.
    |
    */
    'use_default_headers' => false,
    'default_header_list' => [
        'Content-Type',
        'Content-Length',
        'Access-Control-Allow-Origin',
        'Server',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Pretty print
    |--------------------------------------------------------------------------
    |
    | Enables pretty print for mocks created with automock, when the responses
    | Content-Type is application/json.
    |
    */
    'json_pretty_print' => true,

    /*
    |--------------------------------------------------------------------------
    | Request filters
    |--------------------------------------------------------------------------
    |
    | 'url_filters' is a list of URL patterns. Only requests matching these
    | patterns will be automocked. An empty array means all URLs are included.
    |
    | 'filters' is a list of closures that receive a Request and return a
    | boolean. Only requests passing all filters will be automocked.
    |
    */
    'url_filters' => [],
    'filters' => [],

];
