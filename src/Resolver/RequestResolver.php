<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Resolver\Helper\FileNameSanitizer;
use HttpAutomock\Serialization\MessageSerializerFactory;
use HttpAutomock\Service\HttpAutomockFileNameResolver;
use Illuminate\Http\Client\Request;

/**
 * Resolves a unique filename based on HTTP request data.
 *
 * This resolver can generate filenames based on either:
 * 1. A complete serialized request message
 * 2. A combination of request components (URL, method, headers, body)
 *
 * Features:
 * - Optional file system sanitization
 * - Configurable hashing of the output
 * - Substring limiting of the final filename
 * - Individual control over which request components to include
 * - Component-specific options via separate resolvers
 */
class RequestResolver implements FileNameResolverInterface
{
    public function __construct(
        protected MessageSerializerFactory $messageSerializerFactory,
        protected HttpAutomockFileNameResolver $httpAutomockFileNameResolver,
        protected bool $serializeCompleteMessage = true,
        protected bool $url = true,
        protected array $urlOptions = [],
        protected bool $method = true,
        protected array $methodOptions = [],
        protected bool $header = true,
        protected array $headerOptions = [],
        protected bool $body = true,
        protected array $bodyOptions = [],
        protected bool $sanitizeForFileSystems = true,
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $data = str($this->serializeCompleteMessage
            ? $this->messageSerializerFactory->serialize($request->toPsrRequest())
            : $this->partiallySerialize($request, $forWriting, $directory));

        if ($this->sanitizeForFileSystems) {
            $data = FileNameSanitizer::sanitize($data);
        }

        if ($this->hashMethod) {
            $data = str(hash($this->hashMethod, $data->value()));
        }

        if ($this->substring) {
            $data = $data->substr(0, $this->substring);
        }

        return $data->value();
    }

    protected function partiallySerialize(Request $request, bool $forWriting, string $directory): string
    {
        $components = [];

        if ($this->url) {
            $components['url'] = $this->httpAutomockFileNameResolver->resolve([
                'resolver' => RequestUrlResolver::class,
                ...$this->urlOptions,
            ], $request, $forWriting, $directory);
        }

        if ($this->method) {
            $components['method'] = $this->httpAutomockFileNameResolver->resolve([
                'resolver' => RequestMethodResolver::class,
                ...$this->methodOptions,
            ], $request, $forWriting, $directory);
        }

        if ($this->header) {
            $components['method'] = $this->httpAutomockFileNameResolver->resolve([
                'resolver' => RequestHeaderResolver::class,
                ...$this->headerOptions,
            ], $request, $forWriting, $directory);
        }

        if ($this->body) {
            $components['method'] = $this->httpAutomockFileNameResolver->resolve([
                'resolver' => RequestBodyResolver::class,
                ...$this->bodyOptions,
            ], $request, $forWriting, $directory);
        }

        return json_encode($components);
    }
}
