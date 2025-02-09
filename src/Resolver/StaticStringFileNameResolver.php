<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class StaticStringFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        protected string $value,
    ) {
    }

    public function resolve(Request $request, bool $forWriting): string
    {
        return $this->value;
    }
}
