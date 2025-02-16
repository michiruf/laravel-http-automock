<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class CountFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        public int $count = 1,
    ) {
    }

    function resolve(Request $request, bool $forWriting, string $directory): string
    {
        return $forWriting
            ? $this->count++
            : $this->count;
    }
}
