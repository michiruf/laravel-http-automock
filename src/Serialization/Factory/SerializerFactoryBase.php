<?php

namespace HttpAutomock\Serialization\Factory;

/**
 * @deprecated
 */
abstract class SerializerFactoryBase
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
}
