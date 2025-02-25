<?php

namespace HttpAutomock;

use GuzzleHttp\Promise\Create;
use HttpAutomock\Event\RealRequestSendingEvent;
use HttpAutomock\Exceptions\PreventedRequestException;
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
use Psr\Http\Message\MessageInterface;
use SplFileInfo;

class HttpAutomock
{
    protected bool $registered = false;

    protected array $prunedFiles = [];

    public function __construct(
        protected HttpAutomockOptions $options,
        protected HttpAutomockFileNameResolver $fileNameResolver,
        protected MessageSerializerFactory $messageSerializerFactory,
    ) {}

    public function enable(): static
    {
        $this->options->enabled = true;

        if (! $this->registered) {
            $this->registerMockHandler();
            $this->registerPreventRequestsHandler();
            $this->registerResponseHandler();
            $this->registered = true;
        }

        return $this;
    }

    public function disable(): static
    {
        $this->options->enabled = false;

        return $this;
    }

    protected function registerMockHandler(): void
    {
        Http::fake(function (Request $request) {
            if (! $this->options->enabled() || $this->requestFiltered($request)) {
                return null;
            }

            if ($this->options->pruneOnce()) {
                $this->performPruning();
            }

            $filePath = $this->resolveMockPath($request, false);

            if ($this->canMockRequestForFile($filePath)) {
                $fileContent = File::get($filePath);
                $response = $this->deserializeResponse($fileContent);

                return Create::promiseFor($response);
            }

            return null;
        });
    }

    protected function registerPreventRequestsHandler(): void
    {
        Event::listen(RealRequestSendingEvent::class, function (RealRequestSendingEvent $event) {
            if (! $this->options->enabled() || $this->requestFiltered($event->request)) {
                return;
            }

            // Cancel early to avoid resolving the file path
            if (! $this->options->preventRealRequests() && ! $this->options->preventUnknownRealRequests()) {
                return;
            }

            $filePath = $this->resolveMockPath($event->request, false);
            $fileExists = File::exists($filePath);

            if (! $fileExists) {
                if ($this->options->preventRealRequests()) {
                    throw new PreventedRequestException($event->request, $filePath, false);
                }

                $requestIsKnown = in_array($filePath, $this->prunedFiles);
                if ($this->options->preventUnknownRealRequests() && ! $requestIsKnown) {
                    throw new PreventedRequestException($event->request, $filePath, true);
                }
            }
        });
    }

    protected function registerResponseHandler(): void
    {
        Event::listen(function (ResponseReceived $event) {
            if (! $this->options->enabled() || $this->requestFiltered($event->request)) {
                return;
            }

            $filePath = $this->resolveMockPath($event->request, true);

            if ($this->options->validateMocks() && File::exists($filePath)) {
                expect(File::get($filePath))->toBe($this->serializeResponse($event->response));
            }

            if ($this->options->pruneOnce()) {
                $this->performPruning();
            }

            if ($this->canSaveResponse($event->response, $filePath)) {
                File::ensureDirectoryExists(dirname($filePath));
                File::put($filePath, $this->serializeResponse($event->response));
            }
        });
    }

    protected function resolveMockPath(Request $request, bool $forWriting): string
    {
        $directory = str($this->testDirectory());

        $fileName = $this->fileNameResolver->resolve($this->options->fileNameResolver(), $request, $forWriting, $directory->value());

        return $directory
            ->append($fileName)
            ->append($this->options->extension())
            ->toString();
    }

    protected function testDirectory(): string
    {
        $testInstance = TestSuite::getInstance();
        $relativePath = str($testInstance->getFilename())
            ->remove($testInstance->rootPath.DIRECTORY_SEPARATOR.$testInstance->testPath)
            ->beforeLast('.')
            ->toString();
        $description = $testInstance->getDescription();

        return str('')
            ->append($testInstance->rootPath.DIRECTORY_SEPARATOR)
            ->append($testInstance->testPath.DIRECTORY_SEPARATOR)
            ->append($this->options->directory())
            ->append($relativePath.DIRECTORY_SEPARATOR)
            ->append($description.DIRECTORY_SEPARATOR)
            ->value();
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

    protected function serializeResponse(Response $response): string
    {
        return $this->messageSerializerFactory
            ->withHeaders($this->options->headers())
            ->prettyPrintJson($this->options->jsonPrettyPrint())
            ->serialize($response->toPsrResponse());
    }

    protected function deserializeResponse(string $response): MessageInterface
    {
        return $this->messageSerializerFactory->deserialize($response);
    }

    protected function canMockRequestForFile(string $filePath): bool
    {
        $fileExists = File::exists($filePath);

        return $fileExists && ! $this->options->renew() && ! $this->options->validateMocks();
    }

    protected function canSaveResponse(Response $response, string $filePath): bool
    {
        $isFake = empty($response->handlerStats());
        if ($isFake && ! $this->options->mockHttpFakes()) {
            return false;
        }

        return ! File::exists($filePath) || $this->options->renew();
    }

    protected function performPruning(): void
    {
        $directory = $this->testDirectory();

        if (! File::isDirectory($directory)) {
            return;
        }

        $this->prunedFiles = collect(File::allFiles($directory))
            ->map(fn (SplFileInfo $file) => $file->getPathname())
            ->toArray();

        File::deleteDirectory($directory);
    }

    public function resolveFileNameUsing(string|callable|FileNameResolverInterface|null $resolver): static
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

    public function preventRealRequests(?bool $prevent = true): static
    {
        $this->options->preventRealRequests = $prevent;

        return $this;
    }

    /**
     * Prevents real request but does allow renewing request that are already "known".
     * A known requests has a file existing for the file name the request gets mapped to.
     */
    public function preventUnknownRealRequests(?bool $prevent = true): static
    {
        $this->options->preventUnknownRealRequests = $prevent;

        return $this;
    }

    /**
     * @param  bool|null  $renew  Renew when file not exists if null, renew always if true, renew never if false
     */
    public function renew(?bool $renew = true): static
    {
        $this->options->renew = $renew;

        return $this;
    }

    public function preventAutoRenew(bool $prevent = true): static
    {
        $this->options->preventAutoRenew = $prevent;

        return $this;
    }

    public function prune(?bool $prune = true, bool $performImmediately = true): static
    {
        if ($performImmediately) {
            $this->performPruning();

            return $this;
        }

        $this->options->prune = $prune;

        return $this;
    }

    public function validateMocks(?bool $validate = true): static
    {
        $this->options->validateMocks = $validate;

        return $this;
    }

    public function mockHttpFakes(?bool $mock = true): static
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
