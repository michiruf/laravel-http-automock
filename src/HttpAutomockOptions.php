<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;

class HttpAutomockOptions
{
    public ?bool $enabled = null;

    public ?string $directory = null;

    public ?string $extension = null;

    public string|Closure|array|FileNameResolverInterface|null $fileNameResolver = null;

    public ?bool $preventRealRequests = null;

    public ?bool $preventUnknownRealRequests = null;

    public ?bool $renew = null;

    public array|bool|null $headers = null;

    public ?bool $jsonPrettyPrint = null;

    /** @var string[]|null */
    public ?array $urlFilters = null;

    /** @var Closure<Request, bool>[]|null */
    public ?array $filters = null;

    public function __construct(
        protected Repository $config,
    ) {
    }

    protected function commandArg(string $argName): mixed
    {
        return null;
    }

    public function enabled(): bool
    {
        return $this->enabled
            ?? $this->commandArg('enabled')
            ?? $this->config->get('http-automock.enabled', true);
    }

    public function directory(): string
    {
        return $this->directory
            ?? $this->commandArg('directory')
            ?? $this->config->get('http-automock.directory', '.pest/automock');
    }

    public function extension(): string
    {
        return $this->extension
            ?? $this->commandArg('extension')
            ?? $this->config->get('http-automock.extension', '.mock');
    }

    public function fileNameResolver(): FileNameResolverInterface|array|string|Closure
    {
        return $this->fileNameResolver
            ?? $this->commandArg('file-name-resolver')
            ?? $this->config->get('http-automock.default_filename_resolver', 'stack');
    }

    public function preventRealRequests(): bool
    {
        return $this->preventRealRequests
            ?? $this->commandArg('prevent-real-requests')
            ?? $this->config->get('http-automock.prevent_real_requests', false);
    }

    public function preventUnknownRealRequests(): bool
    {
        return $this->preventUnknownRealRequests
            ?? $this->commandArg('prevent-unknown-real-requests')
            ?? $this->config->get('http-automock.prevent_unknown_real_requests', false);
    }

    public function renew(): bool
    {
        return $this->renew
            ?? $this->commandArg('renew')
            ?? $this->config->get('http-automock.renew', false);
    }

    public function headers(): bool|array
    {
        $defaultHeaders = $this->config->get('http-automock.use_default_headers', false)
            ? $this->config->get('http-automock.default_header_list', [])
            : [];

        $headers = $this->headers
            ?? $this->commandArg('headers')
            ?? $defaultHeaders;

        return match ($headers) {
            false => [],
            true => ['*'],
            default => $headers,
        };
    }

    public function jsonPrettyPrint(): bool
    {
        return $this->jsonPrettyPrint
            ?? $this->commandArg('json-pretty-print')
            ?? $this->config->get('http-automock.json_pretty_print', false);
    }

    public function urlFilters(): array
    {
        return $this->urlFilters ?? [];
    }

    /**
     * @return array<int|string, Closure>
     */
    public function filters(): array
    {
        return $this->filters ?? [];
    }
}
