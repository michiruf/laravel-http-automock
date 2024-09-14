<?php

namespace HttpAutomock\Serialization\Factory;

use HttpAutomock\Serialization\RequestSerializerInterface;

class RequestSerializerFactory
{
    public function withoutHeader(string $header): static
    {
    }

    public function withoutAuthenticationHeader(): static
    {
    }

    public function prettyPrintJson(bool $enabled): static
    {
    }

    public function serializer(): RequestSerializerInterface
    {
    }

    public function serialize(): string
    {

    }
}
