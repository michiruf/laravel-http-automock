<?php

namespace HttpAutomock\Serialization;

interface SerializerInterface
{
    // TODO Is this any good?

    public function serializeWithoutHeader(string $header): static;

    public function serializeWithoutAuthenticationHeader(): static;

    public function serializeJsonPretty(bool $enabled): static;
}
