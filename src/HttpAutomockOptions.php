<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;

/**
 * @property bool $enabled
 * @property string $directory
 * @property string $extension
 * @property string|Closure|array|FileNameResolverInterface|null $fileNameResolver
 * @property array|bool|null $headers
 * @property bool $useDefaultHeaders
 * @property bool $defaultHeaderList
 * @property String[] $urlFilters
 * @property Closure<Request, bool>[] $filters
 * @property bool $jsonPrettyPrint
 * @property ?bool $preventRealRequests
 * @property ?bool $preventUnknownRealRequests
 * @property ?bool $renew
 * @property ?bool $prune
 * @property ?bool $preventAutoRenew
 */
class HttpAutomockOptions
{
    public array $options = [];

    protected static array $fallbacks = [
        'fileNameResolver' => 'default_filename_resolver',
    ];

    public function __construct(
        protected Repository $config
    ) {
        $this->urlFilters = [];
        $this->filters = [];
    }

    /** @noinspection PhpUnused */
    protected function modifyHeaders($value): array|bool
    {
        return match ($value) {
            null => $this->useDefaultHeaders
                ? $this->defaultHeaderList
                : [],
            false => [],
            true => ['*'],
            default => $value,
        };
    }

    protected function getConfig(string $name): mixed
    {
        $configName = str($name)->snake()->value();

        $value = $this->config->get("http-automock.$configName");

        if (! $value && isset(static::$fallbacks[$name])) {
            $fallbackName = static::$fallbacks[$name];

            return $this->getConfig($fallbackName);
        }

        return $value;
    }

    public function &__get(string $name)
    {
        $value = &$this->options[$name];

        if (! isset($value)) {
            $value = $this->getConfig($name);
        }

        $methodName = str($name)->ucfirst()->prepend("modify")->value();
        if (method_exists($this, $methodName)) {
            $value = $this->$methodName($value);
        }

        return $value;
    }

    public function __set(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

//    public function __call(string $name, array $arguments)
//    {
//        return $this->$name ?? $arguments[0];
//    }
}
