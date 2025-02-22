<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Helper\FileNameSanitizer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;

/**
 * Resolves a filename based on HTTP request headers.
 *
 * This resolver generates a filename string by processing the headers of an HTTP request.
 * It allows for selective inclusion/exclusion of specific headers and provides options
 * for sanitizing and hashing the resulting filename.
 *
 * Features:
 * - Selectively include or exclude specific headers
 * - Default exclusion of sensitive headers (Authorization, Cookie)
 * - Optional filesystem-safe name sanitization
 * - Optional hashing of the final string
 * - Optional substring length limitation
 *
 * @implements FileNameResolverInterface
 */
class RequestHeaderResolver implements FileNameResolverInterface
{
    /**
     * @param  array<string>  $includedHeaders  List of header names to include
     * @param  array<string>  $excludedHeaders  List of header names to exclude
     */
    public function __construct(
        protected array $includedHeaders = [],
        protected array $excludedHeaders = ['Authorization', 'Cookie'],
        protected bool $sanitizeForFileSystems = true,
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {}

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $headers = [];

        foreach ($request->headers() as $name => $values) {
            if (! $this->shouldIncludeHeader($name)) {
                continue;
            }

            $headerValue = implode(',', $values);
            $headers[] = "$name: $headerValue";
        }

        $headers = implode(', ', $headers);

        if ($this->sanitizeForFileSystems) {
            $headers = FileNameSanitizer::sanitize($headers);
        }

        if ($this->hashMethod) {
            $headers = str(hash($this->hashMethod, $headers->value()));
        }

        if ($this->substring) {
            $headers = $headers->substr(0, $this->substring);
        }

        return $headers->value();
    }

    protected function shouldIncludeHeader(string $headerName): bool
    {
        $headerName = strtolower($headerName);

        if (! empty($this->includedHeaders)) {
            $includedHeadersLower = Arr::map($this->includedHeaders, fn (string $headerName) => strtolower($headerName));

            if (! in_array($headerName, $includedHeadersLower)) {
                return false;
            }
        }

        $excludedHeadersLower = Arr::map($this->excludedHeaders, fn (string $headerName) => strtolower($headerName));

        return ! in_array($headerName, $excludedHeadersLower);
    }
}
