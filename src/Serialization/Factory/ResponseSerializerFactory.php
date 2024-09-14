<?php

namespace HttpAutomock\Serialization\Factory;

use HttpAutomock\Serialization\ResponseSerializer;
use HttpAutomock\Serialization\ResponseSerializerInterface;
use Illuminate\Http\Client\Response;

/**
 * @deprecated
 */
class ResponseSerializerFactory extends SerializerFactoryBase
{
    public function serializer(): ResponseSerializerInterface
    {
        return new ResponseSerializer($this->withoutHeaders, $this->prettyPrintJson);
    }

    public function serialize(Response $response): string
    {
        return $this->serializer()->serialize($response);
    }

    public function deserialize(string $response): Response
    {
        return $this->serializer()->deserialize($response);
    }
}
