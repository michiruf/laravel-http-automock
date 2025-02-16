<?php

use HttpAutomock\Serialization\MessageSerializerFactory;

it('has the test server started', function () {
    $response = Http::get('http://localhost:9337/hello-world');
    expect($response->body())->toBe("Hello World!");
});

it('can get the hot coffee response from the test server', function () {
    $response = Http::get('http://localhost:9337/coffee/hot');
    expect($response->body())->toStartWith("[\n    {\n        \"title\": \"Black Coffee\",");
});

it('can serialize the hot coffee response', function() {
    $response = Http::get('http://localhost:9337/coffee/hot');

    $serializer = app(MessageSerializerFactory::class)->withHeaders(['*'])->prettyPrintJson(true);
    $serialized = $serializer->serialize($response->toPsrResponse());

    expect($serialized)->toMatchSnapshot();
});
