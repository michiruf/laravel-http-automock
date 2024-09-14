<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;

class RequestSerializer implements RequestSerializerInterface
{
    public function serialize(RequestInterface $request): string
    {
        return Message::toString($request);
    }

    public function deserialize(string $request): RequestInterface
    {
        return Message::parseRequest($request);
    }
}
