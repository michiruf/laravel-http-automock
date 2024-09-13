<?php

namespace HttpAutomock;

use Closure;
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

    /** @see static::renew() */
    protected ?bool $renew = null;

    protected ?bool $jsonPrettyPrint = null;

    /** @var String[] */
    protected array $urlFilters = [];

    /** @var Closure<Request, bool>[] */
    protected array $filters = [];

    public function enable(): static
    {
        $this->enabled = true;

        if (! $this->registered) {
            $this->registerFakeHandler();
            $this->registerResponseEventHandler();
            if (config('http-automock.xdebug_develop_mode_compat')) {
                $this->registerXdebugCompatExceptionsMiddleware();
            }
            $this->registered = true;
        }

        return $this;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    protected function registerFakeHandler(): void
    {
        Http::fake(function (Request $request) {
            if (! $this->enabled || $this->requestFiltered($request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($request, false);

            if (File::exists($filePath) && $this->renew !== true) {
                $content = File::get($filePath);

                return Http::response($content);
            } elseif ($this->renew === false) {
                // We would like to throw an exception here, but when we do this, there sometimes
                // will occur a segmentation fault, if xdebug develop mode is enabled.
                // @see https://xdebug.org/docs/develop
                // To avoid this, we separate the validation in a middleware if the compat flag is enabled.
                // @see registerExceptionsMiddleware()
                if (config('http-automock.xdebug_develop_mode_compat')) {
                    // The Http response with 599 is faked, to ensure that no request will be made ever.
                    return Http::response(status: 599);
                } else {
                throw new RuntimeException('Tried to send a request that has renewing disallowed');
            }
            }

            return null;
        });
    }

    protected function registerResponseEventHandler(): void
    {
        Event::listen(function (ResponseReceived $event) {
            if (! $this->enabled || $this->requestFiltered($event->request)) {
                return null;
            }

            $filePath = $this->resolveFilePath($event->request, true);

            if (! File::exists($filePath) || $this->renew === true) {
                $content = $event->response;

                $jsonPrettyPrint = $this->jsonPrettyPrint !== null ? $this->jsonPrettyPrint : config('http-automock.json_prettyprint');
                if ($jsonPrettyPrint && str($content->header('content-type'))->startsWith('application/json')) {
                    $content = json_encode($content->json(), JSON_PRETTY_PRINT);
                }

                File::ensureDirectoryExists(dirname($filePath));
                File::put($filePath, $content);
            }
        });
    }

    protected function registerXdebugCompatExceptionsMiddleware(): void
    {
        // This middleware ensures, that we can throw exceptions when we need to, since
        // it sometimes causes a segmentation fault when throwing exceptions in a callback
        // inside Http::fake(), if xdebug develop mode is enabled.
        // @see registerFakeHandler()

        Http::globalRequestMiddleware(function ($guzzleRequest) {
            // Wrap GuzzleHttp\Psr7\Request in a Illuminate\Http\Client\Request
            $request = new Request($guzzleRequest);

            if (! $this->enabled || $this->renew !== false || $this->requestFiltered($request)) {
                return $guzzleRequest;
            }

            $filePath = $this->resolveFilePath($request, false);

            if (! File::exists($filePath)) {
                throw new RuntimeException('Tried to send a request that has renewing disallowed');
            }

            return $guzzleRequest;
        });
    }

    protected function resolveFilePath(Request $request, bool $new): string
    {
        $testInstance = TestSuite::getInstance();
        $relativePath = str($testInstance->getFilename())
            ->remove($testInstance->rootPath.DIRECTORY_SEPARATOR.$testInstance->testPath)
            ->beforeLast('.')
            ->toString();
        $description = $testInstance->getDescription();

        if ($new) {
            $this->count++;
        }

        $fileName = $this->resolveFileName($request, $this->count);

        return str('')
            ->append($testInstance->rootPath.DIRECTORY_SEPARATOR)
            ->append($testInstance->testPath.DIRECTORY_SEPARATOR)
            ->append(config('http-automock.directory'))
            ->append($relativePath.DIRECTORY_SEPARATOR)
            ->append($description.DIRECTORY_SEPARATOR)
            ->append($fileName)
            ->append(config('http-automock.extension'))
            ->toString();
    }

    protected function resolveFileName(Request $request, int $count): string
    {
        return match (config('http-automock.filename_resolution_strategy')) {
            'count' => $count,
            'url_md5' => md5($request->url()),
            'data_md5' => md5(dd(json_encode($request))) // TODO Test this
        };
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

    /**
     * @param  bool|null  $renew  Renew when file not exists if null, renew always if true, renew never if false
     */
    public function renew(?bool $renew = true): static
    {
        $this->renew = $renew;

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

    // TODO Test all filters
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
