<?php

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

require __DIR__.'/../../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/hello-world', function (ServerRequestInterface $request, ResponseInterface $response) {
    $response->getBody()->write("Hello World!");
    return $response;
});

$app->get('/coffee/hot', function () {
    return Message::parseResponse(file_get_contents(__DIR__.'/Response/coffee-hot.json'));
});

$app->run();
