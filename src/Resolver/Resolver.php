<?php

namespace HttpAutomock\Resolver;

use Closure;
use Illuminate\Http\Client\Request;

/**
 * Resolves file names for HTTP requests using a callable function.
 *
 * This resolver allows for custom file name resolution logic to be provided
 * via a closure, providing flexibility in how request data is mapped to file names.
 *
 * Note: The class is suffixed with resolver intentionally to avoid collisions
 * with the internal name callable for developers.
 *
 * @internal
 */
class Resolver implements FileNameResolverInterface
{
    public function __construct(
        protected Closure $callable
    ) {
    }

    function resolve(Request $request, bool $forWriting, string $directory): string
    {
        return $this->callable->call($this, $request, $forWriting, $directory);
    }
}
