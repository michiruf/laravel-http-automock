<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;

trait HttpAutomockOptions
{
    protected bool $enabled = false;

    protected string|Closure|array|FileNameResolverInterface|null $fileNameResolver = null;

    protected bool $preventRealRequests = false;

    protected bool $preventUnknownRealRequests = false;

    protected bool $renew = false;

    protected array|bool|null $headers = null;

    protected ?bool $jsonPrettyPrint = null;

    /** @var String[] */
    protected array $urlFilters = [];

    /** @var Closure<Request, bool>[] */
    protected array $filters = [];

    protected function __construct(
        protected Repository $config,
    ) {
    }

    protected function getEnabled(): bool
    {
        // TODO && $this->config...
        return $this->enabled;
    }

    protected function getDirectory(): string
    {
        return $this->config->get('http-automock.directory');
    }

    protected function getExtension(): string
    {
        return $this->config->get('http-automock.extension');
    }

    protected function getFileNameResolver(): FileNameResolverInterface|array|string|Closure
    {
        return $this->fileNameResolver ?? $this->config->get('http-automock.default_filename_resolver');
    }

    protected function getPreventRealRequests(): bool
    {
        return $this->preventRealRequests;
    }

    protected function getPreventUnknownRealRequests(): bool
    {
        return $this->preventUnknownRealRequests;
    }

    protected function getRenew(): bool
    {
        return $this->renew;
    }

    protected function getHeaders(): bool|array
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

    protected function getJsonPrettyPrint(): bool
    {
        return $this->jsonPrettyPrint ?? $this->config->get('http-automock.json_pretty_print', false);
    }

    protected function getUrlFilters(): array
    {
        return $this->urlFilters;
    }

    /**
     * @return array<int|string, Closure>
     */
    protected function getFilters(): array
    {
        return $this->filters;
    }
}
