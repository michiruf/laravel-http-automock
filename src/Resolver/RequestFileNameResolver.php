<?php

namespace HttpAutomock\Resolver;

use HttpAutomock\Serialization\Factory\MessageSerializerFactory;
use HttpAutomock\Serialization\PsrMessageSerializer;
use Illuminate\Http\Client\Request;
use RuntimeException;

class RequestFileNameResolver implements RequestFileNameResolverInterface
{
    public int $count;

    public function __construct(
        protected MessageSerializerFactory $messageSerializerFactory
    ) {
        $this->count = 0;
    }

    public function resolve(string|callable|null $resolutionStrategy, Request $request, bool $forWriting): string
    {
        if (is_callable($resolutionStrategy)) {
            return $resolutionStrategy($request);
        }

        if (! $resolutionStrategy) {
            $resolutionStrategy = config('http-automock.filename_resolution_strategy');
        }

        return match ($resolutionStrategy) {
            'count' => $this->count($forWriting),
            'url_md5' => $this->urlMd5($request),
            'data_md5' => $this->dataMd5($request),
            default => throw new RuntimeException("File name resolution strategy '$resolutionStrategy' not implemented"),
        };
    }

    protected function count(bool $forWriting): int
    {
        return $forWriting
            ? ++$this->count
            : $this->count;
    }

    protected function urlMd5(Request $request): string
    {
        return md5($request->url());
    }

    protected function dataMd5(Request $request): string
    {
        $content = $this->messageSerializerFactory->serialize($request->toPsrRequest());
        return md5($content);
    }
}
