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
                '*' => ['count', 'http_method', 'url_hash']
            ],
            'delimiter' => '_',
        ],

        'count' => \HttpAutomock\Resolver\CountResolver::class,

        'http_method' => \HttpAutomock\Resolver\RequestMethodResolver::class,

        'url_hash' => [
            'resolver' => \HttpAutomock\Resolver\RequestUrlResolver::class,
            'hashMethod' => 'xxh32',
        ],

        'data_hash' => [
            'resolver' => \HttpAutomock\Resolver\RequestDataResolver::class,
            'hashMethod' => 'xxh32',
        ]

    ],

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
    'json_prettyprint' => true,

];
