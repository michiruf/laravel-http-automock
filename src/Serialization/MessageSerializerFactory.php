<?php

namespace HttpAutomock\Serialization;

use Psr\Http\Message\MessageInterface;

class MessageSerializerFactory
{
    protected array $headers = [];

    protected bool $prettyPrintJson = false;

    public function withHeaders(array $headers): static
    {
        $this->headers = $headers;

        return $this;
    }

    public function prettyPrintJson(bool $enabled): static
    {
        $this->prettyPrintJson = $enabled;

        return $this;
    }

    public function serializer(): PsrMessageSerializerInterface
    {
        return new PsrMessageSerializer($this->headers, $this->prettyPrintJson);
    }

    public function serialize(MessageInterface $message): string
    {
        return $this->serializer()->serialize($message);
    }

    public function deserialize(string $request): MessageInterface
    {
        return $this->serializer()->deserialize($request);
    }
}
