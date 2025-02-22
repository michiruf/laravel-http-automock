<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Input\ArgvInput;

class HttpAutomockOptions
{
    public ?bool $enabled = null;

    public ?string $directory = null;

    public ?string $extension = null;

    public string|Closure|array|FileNameResolverInterface|null $fileNameResolver = null;

    public ?bool $preventRealRequests = null;

    public ?bool $preventUnknownRealRequests = null;

    public ?bool $renew = null;

    public bool $preventAutoRenew = false;

    public ?bool $mockHttpFakes = null;

    public array|bool|null $headers = null;

    public ?bool $jsonPrettyPrint = null;

    /** @var string[]|null */
    public ?array $urlFilters = null;

    /** @var Closure<Request, bool>[]|null */
    public ?array $filters = null;

    /** @var Collection<int, string> */
    protected Collection $commandArgs;

    public function __construct(
        protected Repository $config,
    ) {
        $this->commandArgs = collect((new ArgvInput)->getRawTokens());
    }

    protected function hasCommandOption(string $argName): ?bool
    {
        $value = $this->commandArgs->contains("--automock-$argName");

        return $value ?: null;
    }

    /**
     * @param  ?callable(string): mixed  $transformValue
     */
    protected function commandArg(string $argName, ?callable $transformValue = null): mixed
    {
        /** @var Stringable $arg */
        $arg = collect($this->commandArgs)
            ->map(fn (string $arg) => str($arg))
            ->first(fn (Stringable $arg) => $arg->startsWith("--automock-$argName="));

        if (! $arg) {
            return null;
        }

        $value = $arg->after('=')->value();

        if ($transformValue) {
            $value = $transformValue($value);
        }

        return $value ?: null;
    }

    public function enabled(): bool
    {
        return $this->enabled
            ?? $this->hasCommandOption('enabled')
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

    public function fileNameResolver(): string|Closure|array|FileNameResolverInterface
    {
        return $this->fileNameResolver
            ?? $this->commandArg('file-name-resolver')
            ?? $this->config->get('http-automock.default_filename_resolver', 'stack');
    }

    public function preventRealRequests(): bool
    {
        return $this->preventRealRequests
            ?? $this->hasCommandOption('prevent-real-requests')
            ?? $this->config->get('http-automock.prevent_real_requests', false);
    }

    public function preventUnknownRealRequests(): bool
    {
        return $this->preventUnknownRealRequests
            ?? $this->hasCommandOption('prevent-unknown-real-requests')
            ?? $this->config->get('http-automock.prevent_unknown_real_requests', false);
    }

    public function renew(): bool
    {
        return $this->renew
            ?? ($this->preventAutoRenew ? false : null)
            ?? $this->hasCommandOption('renew')
            ?? $this->config->get('http-automock.renew', false);
    }

    public function mockHttpFakes(): bool
    {
        return $this->mockHttpFakes
            ?? $this->hasCommandOption('mock-http-fakes')
            ?? $this->config->get('http-automock.mock_http_fakes', false);
    }

    /**
     * @return string[]
     */
    public function headers(): array
    {
        $defaultHeaders = $this->config->get('http-automock.use_default_headers', false)
            ? $this->config->get('http-automock.default_header_list', [])
            : [];

        $headers = $this->headers
            ?? $this->commandArg('headers', fn (string $headers) => explode(',', $headers))
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
            ?? $this->hasCommandOption('json-pretty-print')
            ?? $this->config->get('http-automock.json_pretty_print', false);
    }

    /**
     * @return array<int|string, string>
     */
    public function urlFilters(): array
    {
        return $this->urlFilters
            ?? $this->hasCommandOption('url-filters')
            ?? $this->config->get('http-automock.url_filters', []);
    }

    /**
     * @return array<int|string, callable(Request): bool>
     */
    public function filters(): array
    {
        return $this->filters ?? [];
    }
}
