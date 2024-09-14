<?php

use HttpAutomock\Serialization\RequestSerializer;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;

it('can serialize a request', function () {
    Http::withRequestMiddleware(function (RequestInterface $request) {
        $serialized = (new RequestSerializer())->serialize($request);
        expect($serialized)->toMatchSnapshot();

        return $request;
    })
        ->get('https://api.sampleapis.com/coffee/hot');
});

todo('can deserialize a request');
