<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Resolver\Helper\FileNameSanitizer;
use Illuminate\Http\Client\Request;

/**
 * Resolves a request body into a string representation.
 *
 * This resolver processes HTTP request bodies and converts them into strings that can be safely
 * used as filenames. It provides options for sanitization, hashing, and length control.
 */
class RequestBodyResolver implements FileNameResolverInterface
{
    public function __construct(
        protected bool $sanitizeForFileSystems = true,
        protected ?string $hashMethod = null,
        protected ?int $substring = null,
    ) {
    }

    function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $body = str($request->body());

        if ($this->sanitizeForFileSystems) {
            $body = FileNameSanitizer::sanitize($body);
        }

        if ($this->hashMethod) {
            $body = str(hash($this->hashMethod, $body->value()));
        }

        if ($this->substring) {
            $body = $body->substr(0, $this->substring);
        }

        return $body->value();
    }
}
