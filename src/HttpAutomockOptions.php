<?php

namespace HttpAutomock;

use Closure;
use Error;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;

/**
 * @property bool $enabled
 * @property string $directory
 * @property string $extension
 * @property string|Closure|array|FileNameResolverInterface|null $fileNameResolver
 * @property string $defaultFilenameResolver
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
    protected array $options = [];

    protected static array $existingOptions = [
        'enabled',
        'directory',
        'extension',
        'fileNameResolver',
        'defaultFilenameResolver',
        'headers',
        'useDefaultHeaders',
        'defaultHeaderList',
        'urlFilters',
        'filters',
        'jsonPrettyPrint',
        'preventRealRequests',
        'preventUnknownRealRequests',
        'renew',
        'prune',
        'preventAutoRenew',
    ];

    public function __construct(
        protected Repository $config
    ) {
        $this->urlFilters = [];
        $this->filters = [];
    }

    /** @noinspection PhpUnused */
    protected function fileNameResolver(): string|Closure|array|FileNameResolverInterface|null
    {
        return $this->getInstanceValue('fileNameResolver') ?? $this->defaultFilenameResolver;
    }

    /** @noinspection PhpUnused */
    protected function headers(): array|bool
    {
        $headers = $this->getInstanceValue('headers');

        return match ($headers) {
            null => $this->useDefaultHeaders
                ? $this->defaultHeaderList
                : [],
            false => [],
            true => ['*'],
            default => $headers,
        };
    }

    protected function &getPropagatedValue(string $name): mixed
    {
        $value = &$this->getInstanceValue($name);

        if (!isset($value)) {
            // TODO $this->getCommandArgument($name)
        }

        if (!isset($value)) {
            $value = $this->getConfigValue($name);
        }

        return $value;
    }

    protected function &getInstanceValue(string $name): mixed
    {
        $value = &$this->options[$name];

        if (!isset($value)) {
            $value = null;
        }

        return $value;
    }

    protected function getConfigValue(string $name): mixed
    {
        $configName = str($name)->snake()->value();

        return $this->config->get("http-automock.$configName");
    }

    public function &__get(string $name)
    {
        if (!in_array($name, static::$existingOptions)) {
            // e.g. "ErrorException: Undefined property: Bar::$foor"
            throw new Error("Undefined property: HttpAutomockOptions::$name");
        }

        $value = method_exists($this, $name)
            ? $this->{$name}()
            : null;

        if (!isset ($value)) {
            $value = &$this->getPropagatedValue($name);
        }

        // TODO Throw uninitialized error

        return $value;
    }

    public function __set(string $name, $value): void
    {
        $this->options[$name] = $value;
    }
}
