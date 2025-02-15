<?php

namespace HttpAutomock\Resolver;

use Closure;
use Illuminate\Http\Client\Request;

class CallableFileNameResolver implements RequestFileNameResolverInterface
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
