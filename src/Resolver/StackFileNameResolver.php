<?php

namespace HttpAutomock\Resolver;

use Exception;
use HttpAutomock\Service\HttpAutomockFileNameResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;

class StackFileNameResolver implements RequestFileNameResolverInterface
{
    /**
     * @param  array<string, array<class-string>>  $filenameResolvers
     */
    public function __construct(
        protected array $filenameResolvers,
        protected HttpAutomockFileNameResolver $httpAutomockFileNameResolver,
        protected ?string $delimiter = null,
    ) {
    }

    function resolve(Request $request, bool $forWriting, string $directory): string
    {
        // Receive the first set of resolvers that do match the url
        $stackResolvers = Arr::first($this->filenameResolvers, fn ($resolvers, $url) => str($request->url())->is($url));

        if (! $stackResolvers) {
            throw new Exception("No stack resolver matches the url for this request: {$request->url()}");
        }

        return collect($stackResolvers)
            ->map(fn ($stackResolver) => $this->httpAutomockFileNameResolver->resolve($stackResolver, $request, $forWriting, $directory))
            ->join($this->delimiter ?? '');
    }
}
