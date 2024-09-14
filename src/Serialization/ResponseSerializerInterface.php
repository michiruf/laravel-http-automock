<?php

namespace HttpAutomock\Serialization;

use Psr\Http\Message\ResponseInterface;

interface ResponseSerializerInterface extends SerializerInterface
{
    public function serialize(ResponseInterface $response): string;

    public function deserialize(string $response): ResponseInterface;
}
