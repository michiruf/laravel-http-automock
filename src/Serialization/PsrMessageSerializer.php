<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;

class PsrMessageSerializer implements PsrMessageSerializerInterface
{
    public function __construct(
        protected array $headers,
        protected bool $prettyPrintJson,
    ) {
    }

    public function serialize(MessageInterface $message): string
    {
        $message = $this->filterHeaders($message);
        $message = $this->prettyPrintJson($message);

        return Message::toString($message);
    }

    public function deserialize(string $message): MessageInterface
    {
        return Message::parseResponse($message);
    }

    protected function filterHeaders(MessageInterface $message): MessageInterface
    {
        $messageHeaders = array_keys($message->getHeaders());

        foreach ($messageHeaders as $messageHeader) {
            foreach ($this->headers as $filter) {
                if (str($messageHeader)->is($filter)) {
                    continue 2;
                }
            }

            $message = $message->withoutHeader($messageHeader);
        }

        return $message;
    }

    protected function prettyPrintJson(MessageInterface $message): MessageInterface
    {
        if (! $this->prettyPrintJson) {
            return $message;
        }

        $content = (string) $message->getBody();

        if (json_validate($content)) {
            $decoded = json_decode($content);
            $content = json_encode($decoded, JSON_PRETTY_PRINT);
        }

        return $message->withBody(Utils::streamFor($content));
    }
}
