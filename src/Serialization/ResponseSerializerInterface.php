<?php

namespace HttpAutomock\Serialization;


use Illuminate\Http\Client\Response;

/**
 * @deprecated
 */
interface ResponseSerializerInterface
{
    public function serialize(Response $response): string;

    public function deserialize(string $response): Response;
}
