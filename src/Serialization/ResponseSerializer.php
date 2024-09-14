<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;

class ResponseSerializer implements ResponseSerializerInterface
{
    protected bool $jsonPrettyPrint = false;

    public function serialize(ResponseInterface $response): string
    {
        return Message::toString($response);
    }

    public function deserialize(string $response): ResponseInterface
    {
        return Message::parseResponse($response);
    }

    public function serializeJsonPretty(bool $enabled): static
    {
        $this->jsonPrettyPrint = $enabled;

        return $this;
    }
}
