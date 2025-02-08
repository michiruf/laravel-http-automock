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
    | Filename Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | The strategy used name your generated mocks within the context of one
    | test.
    |
    | 'data_md5': Hashes the url and the payload and the header with md5.
    |             Multiple requests with the same data will only get executed
    |             Recommended in general.
    | 'url_md5':  Hashes the url of the request with md5. Multiple requests
    |             with the same url will only get executed once.
    |             Not recommended!
    | 'count':    Uses an increasing integer to name the files. Multiple
    |             requests in one test will get executed multiple times.
    |             Recommended for verbosity.
    |
    */
    'filename_resolution_strategy' => 'count',

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
