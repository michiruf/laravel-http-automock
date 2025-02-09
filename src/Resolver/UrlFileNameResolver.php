<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class UrlFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        protected ?string $hashMethod = null,
    ) {
    }

    public function resolve(Request $request, bool $forWriting): string
    {
        if (! $this->hashMethod) {
            return str($request->url())->replace('/', '-')->value();
        }

        return hash($this->hashMethod, $request->url());
    }
}
