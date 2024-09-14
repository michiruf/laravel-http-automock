<?php

namespace HttpAutomock\Serialization;

use Psr\Http\Message\RequestInterface;

interface RequestSerializerInterface extends SerializerInterface
{
    public function serialize(RequestInterface $request): string;

    public function deserialize(string $request): RequestInterface;
}
