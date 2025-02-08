<?php

namespace HttpAutomock\Serialization;

use Psr\Http\Message\MessageInterface;

interface PsrMessageSerializerInterface
{
    public function serialize(MessageInterface $message): string;

    public function deserialize(string $message): MessageInterface;
}
