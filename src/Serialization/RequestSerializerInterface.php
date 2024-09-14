<?php

namespace HttpAutomock\Serialization;

use Illuminate\Http\Client\Request;

/**
 * @deprecated
 */
interface RequestSerializerInterface
{
    public function serialize(Request $request): string;

    public function deserialize(string $request): Request;
}
