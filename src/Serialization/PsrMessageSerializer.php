<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;

class PsrMessageSerializer implements PsrMessageSerializerInterface
{
    public function __construct(
        protected array $withoutHeaders,
        protected bool $prettyPrintJson,
    ) {
    }

    public function serialize(MessageInterface $message): string
    {
        $message = $this->removeHeaders($message);
        $message = $this->prettyPrintJson($message);

        return Message::toString($message);
    }

    public function deserialize(string $message): MessageInterface
    {
        return Message::parseResponse($message);
    }

    protected function removeHeaders(MessageInterface $message): MessageInterface
    {
        foreach ($this->withoutHeaders as $header) {
            $message = $message->withoutHeader($header);
        }

        return $message;
    }

    protected function prettyPrintJson(MessageInterface $message): MessageInterface
    {
        if (! $this->prettyPrintJson) {
            return $message;
        }

        $content = $message->getBody()->getContents();

        if (json_validate($content)) {
            $decoded = json_decode($content);
            $content = json_encode($decoded, JSON_PRETTY_PRINT);
        }

        return $message->withBody(Utils::streamFor($content));
    }
}
