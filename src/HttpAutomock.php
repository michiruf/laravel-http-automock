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
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Pest\TestSuite;
use RuntimeException;

class HttpAutomock
{
    protected bool $registered = false;

    public function __construct(
        protected HttpAutomockOptions $options,
        protected HttpAutomockFileNameResolver $fileNameResolver,
        protected MessageSerializerFactory $messageSerializerFactory,
    ) {}

    public function enable(): static
    {
        $this->options->enabled = true;

        if (! $this->registered) {
            $this->registerFakeHandler();
            $this->registerResponseEventHandler();
            $this->registered = true;
        }

        return $this;
    }

    public function disable(): static
    {
        $this->options->enabled = false;

        return $this;
    }

    protected function registerFakeHandler(): void
    {
        Http::fake(function (Request $request) {
            if (! $this->options->enabled() || $this->requestFiltered($request)) {
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
            if (! $this->options->enabled() || $this->requestFiltered($event->request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($event->request, true);

            if ($this->canSaveResponse($event->response, $filePath)) {
                $fileContent = $this->messageSerializerFactory
                    ->withHeaders($this->options->headers())
                    ->prettyPrintJson($this->options->jsonPrettyPrint())
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
            ->append($this->options->directory())
            ->append($relativePath.DIRECTORY_SEPARATOR)
            ->append($description.DIRECTORY_SEPARATOR);

        $fileName = $this->fileNameResolver->resolve($this->options->fileNameResolver(), $request, $forWriting, $directory->value());

        return $directory
            ->append($fileName)
            ->append($this->options->extension())
            ->toString();
    }

    protected function requestFiltered(Request $request): bool
    {
        foreach ($this->options->urlFilters() as $urlFilter) {
            /** @see Factory::stubUrl() */
            if (Str::is(Str::start($urlFilter, '*'), $request->url())) {
                return true;
            }
        }

        foreach ($this->options->filters() as $filter) {
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
        $fileMocked = $fileExists && ! $this->options->renew();

        if (! $fileMocked) {
            match (true) {
                $this->options->preventRealRequests() => throw new RuntimeException('Tried to send a real request that was prevented, file: '.$filePath),
                $this->options->preventUnknownRealRequests() && ! $fileExists => throw new RuntimeException('Tried to send an unknown real request that was prevented, file: '.$filePath),
                default => null,
            };
        }

        return $fileMocked;
    }

    protected function canSaveResponse(Response $response, string $filePath): bool
    {
        $isFake = empty($response->handlerStats());
        if ($isFake && ! $this->options->mockHttpFakes()) {
            return false;
        }

        return ! File::exists($filePath) || $this->options->renew();
    }

    public function resolveFileNameUsing(string|Closure|FileNameResolverInterface|null $resolver): static
    {
        $this->fileNameResolver->forgetPreviousInstances();
        $this->options->fileNameResolver = $resolver;

        return $this;
    }

    /**
     * Convenience method to specify file resolution without the need of having to add values to the configuration
     *
     * @param  class-string  $resolver
     */
    public function resolveFileNameUsingResolverAndArgs(string $resolver, array $args = []): static
    {
        $this->fileNameResolver->forgetPreviousInstances();
        $this->options->fileNameResolver = ['resolver' => $resolver, ...$args];

        return $this;
    }

    public function preventRealRequests(bool $prevent = true): static
    {
        $this->options->preventRealRequests = $prevent;

        return $this;
    }

    /**
     * Prevents real request but does allow renewing request that are already "known".
     * A known requests has a file existing for the file name the request gets mapped to.
     */
    public function preventUnknownRealRequests(bool $prevent = true): static
    {
        $this->options->preventUnknownRealRequests = $prevent;

        return $this;
    }

    /**
     * @param  bool|null  $renew  Renew when file not exists if null, renew always if true, renew never if false
     */
    public function renew(bool $renew = true): static
    {
        $this->options->renew = $renew;

        return $this;
    }

    public function preventAutoRenew(bool $prevent = true): static
    {
        $this->options->preventAutoRenew = $prevent;

        return $this;
    }

    public function mockHttpFakes(bool $mock = true): static
    {
        $this->options->mockHttpFakes = $mock;

        return $this;
    }

    /**
     * @param  array|bool|null  $headers  Headers to include in the mock file, null to reset to config value
     */
    public function withHeaders(array|bool|null $headers = true): static
    {
        $this->options->headers = $headers;

        return $this;
    }

    /**
     * @param  bool|null  $prettyPrint  Pretty print json responses, null to reset to config value
     */
    public function jsonPrettyPrint(?bool $prettyPrint = true): static
    {
        $this->options->jsonPrettyPrint = $prettyPrint;

        return $this;
    }

    public function skip(string|callable $url, ?string $alias = null): static
    {
        match (true) {
            is_string($url) => $alias
                ? $this->options->urlFilters[$alias] = $url
                : $this->options->urlFilters[] = $url,
            is_callable($url) => $alias
                ? $this->options->filters[$alias] = $url
                : $this->options->filters[] = $url,
            default => throw new RuntimeException('Invalid filter type'),
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
            Arr::forget($this->options->urlFilters, $alias);
            Arr::forget($this->options->filters, $alias);

            return $this;
        }

        $this->options->urlFilters = [];
        $this->options->filters = [];

        return $this;
    }
}
