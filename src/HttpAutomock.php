<?php

namespace HttpAutomock;

use Closure;
use GuzzleHttp\Promise\Create;
use HttpAutomock\Resolver\FileNameResolverInterface;
use HttpAutomock\Serialization\MessageSerializerFactory;
use HttpAutomock\Service\HttpAutomockFileNameResolver;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Pest\TestSuite;
use RuntimeException;

class HttpAutomock
{
    protected bool $enabled = false;

    protected bool $registered = false;

    protected bool $read = true;

    protected bool $create = true;

    protected bool $update = false;

    protected string|Closure|array|FileNameResolverInterface|null $fileNameResolver = null;

    protected array|bool|null $headers = null;

    protected ?bool $jsonPrettyPrint = null;

    /** @var String[] */
    protected array $urlFilters = [];

    /** @var Closure<Request, bool>[] */
    protected array $filters = [];

    public function __construct(
        protected MessageSerializerFactory $messageSerializerFactory,
        protected HttpAutomockFileNameResolver $httpAutomockFileNameResolver,
    ) {
    }

    public function enable(): static
    {
        $this->enabled = true;

        if (! $this->registered) {
            $this->registerFakeHandler();
            $this->registerResponseEventHandler();
            $this->registered = true;
        }

        return $this;
    }

    public function disable(): static
    {
        $this->enabled = false;

        return $this;
    }

    protected function registerFakeHandler(): void
    {
        Http::fake(function (Request $request) {
            if (! $this->enabled || $this->requestFiltered($request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($request, false);

            if (File::exists($filePath) && $this->read) {
                $fileContent = File::get($filePath);
                $response = $this->messageSerializerFactory->deserialize($fileContent);

                return Create::promiseFor($response);
            } else if(File::exists($filePath) && $this->update) {
                return null;
            } else if(! File::exists($filePath) && $this->create) {
                return null;
            }

            throw new RuntimeException("Tried to send a real request that was prevented");
        });
    }

    protected function registerResponseEventHandler(): void
    {
        Event::listen(function (ResponseReceived $event) {
            if (! $this->enabled || $this->requestFiltered($event->request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($event->request, true);

            if (
                $this->create && ! File::exists($filePath) ||
                $this->update
            ) {
                $jsonPrettyPrint = $this->jsonPrettyPrint !== null
                    ? $this->jsonPrettyPrint
                    : config('http-automock.json_pretty_print');

                $headers = match ($this->headers) {
                    null => config('http-automock.use_default_headers')
                        ? config('http-automock.default_header_list')
                        : [],
                    false => [],
                    true => ['*'],
                    default => $this->headers,
                };

                $fileContent = $this->messageSerializerFactory
                    ->withHeaders($headers)
                    ->prettyPrintJson($jsonPrettyPrint)
                    ->serialize($event->response->toPsrResponse());

                File::ensureDirectoryExists(dirname($filePath));
                File::put($filePath, $fileContent);
            }
        });
    }

    protected function resolveFilePath(Request $request, bool $forWriting): string
    {
        $testInstance = TestSuite::getInstance();
        $relativePath = str($testInstance->getFilename())
            ->remove($testInstance->rootPath.DIRECTORY_SEPARATOR.$testInstance->testPath)
            ->beforeLast('.')
            ->toString();
        $description = $testInstance->getDescription();

        $directory = str('')
            ->append($testInstance->rootPath.DIRECTORY_SEPARATOR)
            ->append($testInstance->testPath.DIRECTORY_SEPARATOR)
            ->append(config('http-automock.directory'))
            ->append($relativePath.DIRECTORY_SEPARATOR)
            ->append($description.DIRECTORY_SEPARATOR);

        $fileNameResolver = $this->fileNameResolver ?? config('http-automock.default_filename_resolver');
        $fileName = $this->httpAutomockFileNameResolver->resolve($fileNameResolver, $request, $forWriting, $directory->value());

        return $directory
            ->append($fileName)
            ->append(config('http-automock.extension'))
            ->toString();
    }

    protected function requestFiltered(Request $request): bool
    {
        foreach ($this->urlFilters as $urlFilter) {
            /** @see Factory::stubUrl() */
            if (Str::is(Str::start($urlFilter, '*'), $request->url())) {
                return true;
            }
        }

        foreach ($this->filters as $filter) {
            if ($filter($request)) {
                return true;
            }
        }

        return false;
    }

    public function resolveFileNameUsing(string|Closure|FileNameResolverInterface|null $resolver): static
    {
        $this->httpAutomockFileNameResolver->forgetPreviousInstances();
        $this->fileNameResolver = $resolver;

        return $this;
    }

    /**
     * Convenience method to specify file resolution without the need of having to add values to the configuration
     *
     * @param  class-string  $resolver
     */
    public function resolveFileNameUsingResolverAndArgs(string $resolver, array $args = []): static
    {
        $this->httpAutomockFileNameResolver->forgetPreviousInstances();
        $this->fileNameResolver = ['resolver' => $resolver, ...$args];

        return $this;
    }

    public function preventRealRequests(): static
    {
        $this->read = true;
        $this->create = false;
        $this->update = false;

        return $this;
    }

    public function preventUnknownRealRequests(): static
    {
        $this->read = true;
        $this->create = false;
        $this->update = true;

        return $this;
    }

    public function renew(): static
    {
        $this->read = false;
        $this->create = true;
        $this->update = true;

        return $this;
    }

    public function renewExisting(): static
    {
        $this->read = false;
        $this->create = false;
        $this->update = true;

        return $this;
    }

    /**
     * @param  array|bool|null  $headers  Headers to include in the mock file, null to reset to config value
     */
    public function withHeaders(array|bool|null $headers = true): static
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param  bool|null  $prettyPrint  Pretty print json responses, null to reset to config value
     */
    public function jsonPrettyPrint(?bool $prettyPrint = true): static
    {
        $this->jsonPrettyPrint = $prettyPrint;

        return $this;
    }

    public function skip(string|callable $url, ?string $alias = null): static
    {
        match (true) {
            is_string($url) => $alias
                ? $this->urlFilters[$alias] = $url
                : $this->urlFilters[] = $url,
            is_callable($url) => $alias
                ? $this->filters[$alias] = $url
                : $this->filters[] = $url,
            default => throw new RuntimeException("Invalid filter type"),
        };

        return $this;
    }

    public function skipUnlessGet(): static
    {
        $this->skip(fn (Request $request) => $request->method() !== 'GET', 'unless-get');

        return $this;
    }

    public function skipGet(): static
    {
        $this->skip(fn (Request $request) => $request->method() === 'GET', 'get');

        return $this;
    }

    public function skipPost(): static
    {
        $this->skip(fn (Request $request) => $request->method() === 'POST', 'post');

        return $this;
    }

    public function skipPut(): static
    {
        $this->skip(fn (Request $request) => $request->method() === 'PUT', 'put');

        return $this;
    }

    public function skipDelete(): static
    {
        $this->skip(fn (Request $request) => $request->method() === 'DELETE', 'delete');

        return $this;
    }

    public function stopSkip(?string $alias = null): static
    {
        if ($alias) {
            Arr::forget($this->urlFilters, $alias);
            Arr::forget($this->filters, $alias);

            return $this;
        }

        $this->urlFilters = [];
        $this->filters = [];

        return $this;
    }
}
