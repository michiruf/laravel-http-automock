<?php

namespace HttpAutomock;

use Closure;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Request;
use InvalidArgumentException;

/**
 * @property-write bool $enabled
 * @property-write string $directory
 * @property-write string $extension
 * @property-write string|Closure|array|FileNameResolverInterface|null $fileNameResolver
 * @property-write string $defaultFilenameResolver
 * @property-write array|bool|null $headers
 * @property-write bool $useDefaultHeaders
 * @property-write bool $defaultHeaderList
 * @property String[] $urlFilters
 * @property Closure<Request, bool>[] $filters
 * @property-write bool $jsonPrettyPrint
 * @property-write ?bool $preventRealRequests
 * @property-write ?bool $preventUnknownRealRequests
 * @property-write ?bool $renew
 * @property-write ?bool $prune
 * @property-write ?bool $preventAutoRenew
 *
 * @method bool enabled()
 * @method string directory()
 * @method string extension()
 * @method string defaultFilenameResolver()
 * @method bool useDefaultHeaders()
 * @method bool defaultHeaderList()
 * @method String[] urlFilters()
 * @method Closure<Request, bool>[] filters()
 * @method bool jsonPrettyPrint()
 * @method bool preventRealRequests()
 * @method bool preventUnknownRealRequests()
 * @method bool renew()
 * @method bool prune()
 * @method bool preventAutoRenew()
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

    public function fileNameResolver(): string|Closure|array|FileNameResolverInterface|null
    {
        return $this->getPropagatedValue('fileNameResolver') ?? $this->defaultFilenameResolver();
    }

    public function headers(): array|bool
    {
        $headers = $this->getPropagatedValue('headers');

        return match ($headers) {
            null => $this->useDefaultHeaders()
                ? $this->defaultHeaderList()
                : [],
            false => [],
            true => ['*'],
            default => $headers,
        };
    }

    protected function &getPropagatedValue(string $name): mixed
    {
        $value = &$this->getInstanceValue($name);

        if (! isset($value)) {
            $value = $this->getCommandArgument($name);
        }

        if (! isset($value)) {
            $value = $this->getConfigValue($name);
        }

        return $value;
    }

    protected function &getInstanceValue(string $name): mixed
    {
        $value = &$this->options[$name];

        if (! isset($value)) {
            $value = null;
        }

        return $value;
    }

    protected function getCommandArgument(string $name): mixed
    {
        // TODO
        return null;
    }

    protected function getConfigValue(string $name): mixed
    {
        $configName = str($name)->snake()->value();

        return $this->config->get("http-automock.$configName");
    }

    public function &__get(string $name): mixed
    {
        static::throwIfOptionNotExists($name);

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $value = &$this->getInstanceValue($name);

        return $value;
    }

    public function __set(string $name, $value): void
    {
        static::throwIfOptionNotExists($name);

        $this->options[$name] = $value;
    }

    public function __call(string $name, array $arguments)
    {
        static::throwIfOptionNotExists($name);

        return $this->getPropagatedValue($name);
    }

    protected static function throwIfOptionNotExists(string $name): void
    {
        if (! in_array($name, static::$existingOptions)) {
            throw new InvalidArgumentException("Undefined option $name");
        }
    }
}
