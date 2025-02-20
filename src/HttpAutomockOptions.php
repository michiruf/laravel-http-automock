<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;

class HttpAutomockOptions
{
    public bool $enabled = false;

    public string|Closure|array|FileNameResolverInterface|null $fileNameResolver = null;

    public bool $preventRealRequests = false;

    public bool $preventUnknownRealRequests = false;

    public bool $renew = false;

    public array|bool|null $headers = null;

    public ?bool $jsonPrettyPrint = null;

    /** @var String[] */
    public array $urlFilters = [];

    /** @var Closure<Request, bool>[] */
    public array $filters = [];

    public function __construct(
        protected Repository $config
    ) {
    }

    public function enabled(): bool
    {
        // TODO && $this->config...
        return $this->enabled;
    }

    public function directory(): string
    {
        return $this->config->get('http-automock.directory');
    }

    public function extension(): string
    {
        return $this->config->get('http-automock.extension');
    }

    public function fileNameResolver(): FileNameResolverInterface|array|string|Closure
    {
        return $this->fileNameResolver ?? $this->config->get('http-automock.default_filename_resolver');
    }

    public function preventRealRequests(): bool
    {
        return $this->preventRealRequests;
    }

    public function preventUnknownRealRequests(): bool
    {
        return $this->preventUnknownRealRequests;
    }

    public function renew(): bool
    {
        return $this->renew;
    }

    public function headers(): bool|array
    {
        return match ($this->headers) {
            null => $this->config->get('http-automock.use_default_headers')
                ? $this->config->get('http-automock.default_header_list')
                : [],
            false => [],
            true => ['*'],
            default => $this->headers,
        };
    }

    public function jsonPrettyPrint(): bool
    {
        return $this->jsonPrettyPrint ?? $this->config->get('http-automock.json_pretty_print', false);
    }

    public function urlFilters(): array
    {
        return $this->urlFilters;
    }

    /**
     * @return array<int|string, Closure>
     */
    public function filters(): array
    {
        return $this->filters;
    }
}
