<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

/**
 * Uses the requests http method to name files.
 *
 * This resolver generates filenames based on the HTTP method of the request.
 * For example, a GET request will result in a file named "GET" or "get"
 * depending on the lowercase setting.
 */
class RequestMethodResolver implements FileNameResolverInterface
{
    public function __construct(
        protected bool $lowercase = false,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        $method = str($request->method());

        if ($this->lowercase) {
            $method = $method->lower();
        }

        return $method->value();
    }
}
