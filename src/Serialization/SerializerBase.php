<?php

namespace HttpAutomock\Serialization;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;

/**
 * @deprecated
 */
abstract class SerializerBase
{
    public function __construct(
        protected array $withoutHeaders,
        protected bool $prettyPrintJson,
    ) {
    }

    public function removeHeaders(MessageInterface $message): MessageInterface
    {
        foreach ($this->withoutHeaders as $header) {
            $message = $message->withoutHeader($header);
        }

        return $message;
    }

    public function prettyPrintJson(MessageInterface $message): MessageInterface
    {
        if (! $this->prettyPrintJson) {
            return $message;
        }

        $content = $message->getBody()->getContents();

        if (json_validate($content)) {
            $decoded = json_decode($content, true);
            $content = json_encode($decoded, JSON_PRETTY_PRINT);
        }

        return $message->withBody(Utils::streamFor($content));
    }
}
