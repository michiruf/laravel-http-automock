<?php

namespace HttpAutomock\Resolver;

use Illuminate\Http\Client\Request;

class UrlFileNameResolver implements RequestFileNameResolverInterface
{
    public function __construct(
        protected ?string $hashMethod = null,
        protected ?bool $removeHttps = true,
        protected ?bool $removeSlashes = true,
    ) {
    }

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        if (! $this->hashMethod) {
            $url = str($request->url());

            if ($this->removeHttps) {
                $url = $url->replace('https://', '');
            }

            if ($this->removeSlashes) {
                $url = $url->replace('/', '-');
            }

            $url = $url->replace(':', '-'); // always remove ':'

            return $url->value();
        }

        return hash($this->hashMethod, $request->url());
    }
}
