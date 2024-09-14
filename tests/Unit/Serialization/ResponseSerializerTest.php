<?php

use HttpAutomock\Serialization\ResponseSerializer;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;

it('can serialize a response', function () {
    Http::withResponseMiddleware(function (ResponseInterface $response) {
        $serialized = (new ResponseSerializer())->serialize($response);
        expect($serialized)->toMatchSnapshot();

        return $response;
    })
        ->get('https://api.sampleapis.com/coffee/hot');
});

todo('can deserialize a response');

todo('can enable pretty printing responses');
