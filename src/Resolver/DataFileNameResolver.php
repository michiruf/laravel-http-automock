<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Serialization\MessageSerializerFactory;
use Illuminate\Http\Client\Request;

/**
 * Generates a filename by hashing request data with configurable components.
 *
 * This resolver creates unique filenames based on request data, allowing selective
 * inclusion/exclusion of different request components (headers, query parameters,
 * body, etc.) when generating the hash.
 *
 * Features:
 * - Selective request component inclusion/exclusion
 * - Optional header filtering
 * - Configurable hash algorithm
 * - Adjustable output length
 * - Custom data transformations
 *
 * Example usage:
 * A POST request with headers and payload might generate:
 * - Hash of all data: "a7d8e9f..."
 * - Hash of body only: "b2c3d4e..."
 * - Hash of specific headers and query: "c5d6e7f..."
 */
class DataFileNameResolver implements RequestFileNameResolverInterface
{
    /**
     * @param  string[]  $includedHeaders
     * @param  string[]  $excludedHeaders
     */
    public function __construct(
        protected MessageSerializerFactory $messageSerializerFactory,
        protected bool $serializeCompleteMessage = true,
        protected bool $includeMethod = true,
        protected bool $includeUrl = true,
        protected array $includedHeaders = [],
        protected array $excludedHeaders = ['Authorization', 'Cookie'],
        protected bool $includeBody = true,
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $content = str($this->serializeCompleteMessage
            ? $this->messageSerializerFactory->serialize($request->toPsrRequest())
            : $this->partiallySerialize($request));

        if ($this->hashMethod) {
            $content = str(hash($this->hashMethod, $content->value()));
        }

        if ($this->substring) {
            $content = $content->substr(0, $this->substring);
        }

        return $content->value();
    }

    protected function partiallySerialize(Request $request): string
    {
        $psrRequest = $request->toPsrRequest();
        $components = [];

        if ($this->includeMethod) {
            $components['method'] = $request->method();
        }

        if ($this->includeUrl) {
            $components['url'] = $request->url();
        }

        foreach ($psrRequest->getHeaders() as $name => $values) {
            if ($this->shouldIncludeHeader($name)) {
                $components['headers'][$name] = $values;
            }
        }

        if ($this->includeBody) {
            $body = $psrRequest->getBody()->getContents();
            if (! empty($body)) {
                $components['body'] = $body;
            }
        }

        return json_encode($components);
    }

    protected function shouldIncludeHeader(string $headerName): bool
    {
        $headerName = strtolower($headerName);

        if (! empty($this->includedHeaders)) {
            return in_array($headerName, array_map('strtolower', $this->includedHeaders));
        }

        return ! in_array($headerName, array_map('strtolower', $this->excludedHeaders));
    }
}
