<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

/**
 * A file name resolver that always returns a predefined static string.
 *
 * This resolver is useful when you need to add scopes to a set of requests in a test.
 */
class StaticStringFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        protected string $value,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        return $this->value;
    }
}
