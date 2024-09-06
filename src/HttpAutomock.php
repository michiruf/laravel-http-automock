<?php

namespace HttpAutomock;

use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Pest\TestSuite;
use RuntimeException;

class HttpAutomock
{
    protected bool $enabled = false;

    protected bool $registered = false;

    /**
     * @var bool|null Renew if null, renew always if true, renew never if false
     */
    protected ?bool $renew = null;

    protected ?bool $jsonPrettyPrint = null;

    public int $count = 0;

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
                throw new RuntimeException('Tried to send a request that has renewing disallowed');
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
        // TODO

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

    public function jsonPrettyPrint(?bool $prettyPrint = true): static
    {
        $this->jsonPrettyPrint = $prettyPrint;

        return $this;
    }

    // TODO Implement & test
    public function skip(string|callable $url): static
    {
        return $this;
    }

    // TODO Test
    public function skipUnlessGet(): static
    {
        $this->skip(fn (Request $request) => $request->method() !== 'GET');

        return $this;
    }
}
