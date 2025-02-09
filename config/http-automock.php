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
    | 'filename_resolution_resolvers' configures the resolvers used to generate
    | a file name for an automock file depending on the request data.
    |
    | 'filename_resolution_delimiter' configures how the resolvers should get
    | put together to build a file name.
    |
    | Packaged options 'filename_resolution_resolvers':
    |
    | * \HttpAutomock\Resolver\CountFileNameResolver::class
    |   | Uses an increasing integer to name the files. Multiple
    |   | requests in one test will get executed multiple times.
    |
    | * \HttpAutomock\Resolver\DataFileNameResolver::class => ['hashMethod' => 'sha256']
    |   | Hashes all request data (url, payload and header) with the given hash
    |   | method. Multiple requests with the same data will only get executed
    |   | once.
    |
    | * \HttpAutomock\Resolver\MethodFileNameResolver::class
    |   | Returns the requests http method.
    |
    | * \HttpAutomock\Resolver\StaticStringFileNameResolver::class => ['value' => 'static_string'],
    |   | Returns the specified string value. Useful to add scopes to a
    |   | requests file name if multiple scopes are needed in a test.
    |
    | * \HttpAutomock\Resolver\UrlFileNameResolver::class => ['hashMethod' => 'md5']
    |   | Hashes the url of the request with the given hash method. Multiple
    |   | requests with the same url will only get executed once.
    |
    */
    'filename_resolution_resolvers' => [
        \HttpAutomock\Resolver\CountFileNameResolver::class,
        \HttpAutomock\Resolver\MethodFileNameResolver::class,
        \HttpAutomock\Resolver\UrlFileNameResolver::class => ['hashMethod' => 'md5'],
    ],
    'filename_resolution_delimiter' => '_',

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
