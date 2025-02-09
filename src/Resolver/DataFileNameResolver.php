<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Serialization\MessageSerializerFactory;
use Illuminate\Http\Client\Request;

class DataFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        protected MessageSerializerFactory $messageSerializerFactory,
        protected ?string $hashMethod = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting): string
    {
        $content = $this->messageSerializerFactory->serialize($request->toPsrRequest());

        if (! $this->hashMethod) {
            return $content;
        }

        return hash($this->hashMethod, $content);
    }
}
