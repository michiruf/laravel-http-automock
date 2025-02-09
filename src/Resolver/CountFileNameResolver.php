<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class CountFileNameResolver implements RequestFileNameResolverInterface
{
    public int $count = 1;

    function resolve(Request $request, bool $forWriting): string
    {
        return $forWriting
            ? $this->count++
            : $this->count;
    }
}
