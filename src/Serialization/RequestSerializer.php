<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Client\Request;
use RuntimeException;

/**
 * @deprecated
 */
class RequestSerializer extends SerializerBase implements RequestSerializerInterface
{
    public function serialize(Request $request): string
    {
        $psrRequest = $request->toPsrRequest();
        $psrRequest = $this->removeHeaders($psrRequest);
        $psrRequest = $this->prettyPrintJson($psrRequest);

        return Message::toString($psrRequest);
    }

    public function deserialize(string $request): Request
    {
        throw new RuntimeException("Not implemented");
    }
}
