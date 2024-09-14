<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Client\Response;

/**
 * @deprecated
 */
class ResponseSerializer extends SerializerBase implements ResponseSerializerInterface
{
    public function serialize(Response $response): string
    {
        $psrResponse = $response->toPsrResponse();
        $psrResponse = $this->removeHeaders($psrResponse);
        $psrResponse = $this->prettyPrintJson($psrResponse);

        return Message::toString($psrResponse);
    }

    public function deserialize(string $response): Response
    {
        return new Response(Message::parseResponse($response));
    }
}
