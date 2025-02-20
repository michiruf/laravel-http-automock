<?php

namespace HttpAutomock;

use Closure;
use GuzzleHttp\Promise\Create;
use HttpAutomock\Resolver\FileNameResolverInterface;
use HttpAutomock\Serialization\MessageSerializerFactory;
use HttpAutomock\Service\HttpAutomockFileNameResolver;
use Illuminate\Config\Repository;
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
    use HttpAutomockOptions {
        HttpAutomockOptions::__construct as protected optionConstruct;
    }

    protected bool $registered = false;

    public function __construct(
        Repository $config,
        protected HttpAutomockFileNameResolver $httpAutomockfileNameResolver,
        protected MessageSerializerFactory $messageSerializerFactory,
    ) {
        $this->optionConstruct($config);
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
            if (! $this->getEnabled() || $this->requestFiltered($request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($request, false);

            if ($this->canMockFileOrThrow($filePath)) {
                $fileContent = File::get($filePath);
                $response = $this->messageSerializerFactory->deserialize($fileContent);

                return Create::promiseFor($response);
            }

            return null;
        });
    }

    protected function registerResponseEventHandler(): void
    {
        Event::listen(function (ResponseReceived $event) {
            if (! $this->getEnabled() || $this->requestFiltered($event->request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($event->request, true);

            if (! File::exists($filePath) || $this->getRenew()) {
                $fileContent = $this->messageSerializerFactory
                    ->withHeaders($this->getHeaders())
                    ->prettyPrintJson($this->getJsonPrettyPrint())
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
            ->append($this->getDirectory())
            ->append($relativePath.DIRECTORY_SEPARATOR)
            ->append($description.DIRECTORY_SEPARATOR);

        $fileName = $this->httpAutomockfileNameResolver->resolve($this->getFileNameResolver(), $request, $forWriting, $directory->value());

        return $directory
            ->append($fileName)
            ->append($this->getExtension())
            ->toString();
    }

    protected function requestFiltered(Request $request): bool
    {
        foreach ($this->getUrlFilters() as $urlFilter) {
            /** @see Factory::stubUrl() */
            if (Str::is(Str::start($urlFilter, '*'), $request->url())) {
                return true;
            }
        }

        foreach ($this->getFilters() as $filter) {
            if ($filter($request)) {
                return true;
            }
        }

        return false;
    }

    protected function canMockFileOrThrow(string $filePath): bool
    {
        $fileExists = File::exists($filePath);

        // Determine whether the file should be loaded first
        $fileMocked = $fileExists && ! $this->getRenew();

        if (! $fileMocked) {
            match (true) {
                $this->getPreventRealRequests() => throw new RuntimeException('Tried to send a real request that was prevented, file: '.$filePath),
                $this->getPreventUnknownRealRequests() && ! $fileExists => throw new RuntimeException('Tried to send an unknown real request that was prevented, file: '.$filePath),
                default => null,
            };
        }

        return $fileMocked;
    }

    public function resolveFileNameUsing(string|Closure|FileNameResolverInterface|null $resolver): static
    {
        $this->httpAutomockfileNameResolver->forgetPreviousInstances();
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
        $this->httpAutomockfileNameResolver->forgetPreviousInstances();
        $this->fileNameResolver = ['resolver' => $resolver, ...$args];

        return $this;
    }

    public function preventRealRequests(bool $prevent = true): static
    {
        $this->preventRealRequests = $prevent;

        return $this;
    }

    /**
     * Prevents real request but does allow renewing request that are already "known".
     * A known requests has a file existing for the file name the request gets mapped to.
     */
    public function preventUnknownRealRequests(bool $prevent = true): static
    {
        $this->preventUnknownRealRequests = $prevent;

        return $this;
    }

    /**
     * @param  bool|null  $renew  Renew when file not exists if null, renew always if true, renew never if false
     */
    public function renew(bool $renew = true): static
    {
        $this->renew = $renew;

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
