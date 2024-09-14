<?php

namespace HttpAutomock\Serialization\Factory;

use HttpAutomock\Serialization\RequestSerializer;
use HttpAutomock\Serialization\RequestSerializerInterface;
use Illuminate\Http\Client\Request;

/**
 * @deprecated
 */
class RequestSerializerFactory extends SerializerFactoryBase
{
    public function serializer(): RequestSerializerInterface
    {
        return new RequestSerializer($this->withoutHeaders, $this->prettyPrintJson);
    }

    public function serialize(Request $request): string
    {
        return $this->serializer()->serialize($request);
    }

    public function deserialize(string $request): Request
    {
        return $this->serializer()->deserialize($request);
    }
}
