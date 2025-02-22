<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Service\HttpAutomockFileNameResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves filenames by combining multiple resolvers in a stack.
 *
 * This resolver takes an array of filename resolvers and combines their results
 * based on URL patterns. It matches the request URL against configured patterns
 * and applies the corresponding stack of resolvers to generate the final filename.
 */
class StackResolver implements FileNameResolverInterface
{
    /**
     * @param  array<string, array<string>&array<class-string>>  $filenameResolvers
     */
    public function __construct(
        protected array $filenameResolvers,
        protected HttpAutomockFileNameResolver $httpAutomockFileNameResolver,
        protected ?string $delimiter = null,
    ) {}

    public function resolve(Request $request, bool $forWriting, string $directory): string
    {
        // Receive the first set of resolvers that do match the url
        $stackResolvers = Arr::first($this->filenameResolvers, fn ($resolvers, $url) => Str::is(Str::start($url, '*'), $request->url()));

        if (! $stackResolvers) {
            throw new RuntimeException("No stack resolver matches the url for this request: {$request->url()}");
        }

        return collect($stackResolvers)
            ->map(fn ($stackResolver) => $this->httpAutomockFileNameResolver->resolve($stackResolver, $request, $forWriting, $directory))
            ->join($this->delimiter ?? '');
    }
}
