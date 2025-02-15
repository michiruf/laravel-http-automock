<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class MethodFileNameResolver implements RequestFileNameResolverInterface
{
    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        return $request->method();
    }
}
