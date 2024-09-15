<?php

namespace HttpAutomock\Serialization;

use Psr\Http\Message\MessageInterface;

class MessageSerializerFactory
{
    protected array $withoutHeaders = [];

    protected bool $prettyPrintJson;

    public function withoutHeader(string|array $header): static
    {
        if (is_array($header)) {
            array_push($this->withoutHeaders, ...$header);
        } else {
            $this->withoutHeaders[] = $header;
        }

        return $this;
    }

    public function prettyPrintJson(bool $enabled): static
    {
        $this->prettyPrintJson = $enabled;

        return $this;
    }

    public function serializer(): PsrMessageSerializerInterface
    {
        return new PsrMessageSerializer($this->withoutHeaders, $this->prettyPrintJson);
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
