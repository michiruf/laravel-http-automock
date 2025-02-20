<?php

namespace HttpAutomock\Service;

use HttpAutomock\Resolver\Resolver;
use HttpAutomock\Resolver\FileNameResolverInterface;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Request;
use RuntimeException;

class HttpAutomockFileNameResolver
{
    protected array $resolvedContainerResolvers = [];

    public function __construct(
        protected Container $container,
        protected Repository $config,
    ) {
    }

    public function resolve(string|array|callable|FileNameResolverInterface $resolver, Request $request, bool $forWriting, string $directory): string
    {
        $resolverInstance = match (true) {
            is_string($resolver) && ! $this->isResolverClass($resolver) => $this->resolveConfigResolver($resolver),
            is_string($resolver) && $this->isResolverClass($resolver) => $this->resolveClassResolver($resolver, []),
            is_array($resolver) => $this->resolveArgsResolver($resolver),
            is_callable($resolver) => $this->resolveCallableResolver($resolver),
            $resolver instanceof FileNameResolverInterface => $resolver,
            default => null,
        };

        if (! $resolverInstance instanceof FileNameResolverInterface) {
            throw new RuntimeException("Resolver '$resolver' not found");
        }

        return $resolverInstance->resolve($request, $forWriting, $directory);
    }

    protected function isResolverClass(string $resolver): bool
    {
        return is_a($resolver, FileNameResolverInterface::class, true);
    }

    protected function resolveConfigResolver(string $resolver): ?FileNameResolverInterface
    {
        $args = $this->config->get("http-automock.filename_resolvers.$resolver");

        if (! $args) {
            return null;
        }

        if (is_string($args)) {
            $args = ['resolver' => $args];
        }

        return $this->resolveArgsResolver($args);
    }

    protected function resolveArgsResolver(array $args): ?FileNameResolverInterface
    {
        $resolverClass = $args['resolver'] ?? null;

        if (! $resolverClass) {
            return null;
        }

        return $this->resolveClassResolver($resolverClass, $args);
    }

    /**
     * @param  class-string  $class
     * @see static::forgetPreviousInstances() on how isntances get cleared
     */
    protected function resolveClassResolver(string $class, array $args): FileNameResolverInterface
    {
        // Manually bind the scoped instance to not lose the resolver state on each filename resolution
        if (! $this->container->resolved($class)) {
            $resolverInstance = $this->container->make($class, $args);
            $this->container->scoped($class, fn (Application $app) => $resolverInstance);
            $this->resolvedContainerResolvers[] = $class;
        }

        $resolverInstance = $this->container->make($class, $args);

        if (! $resolverInstance instanceof FileNameResolverInterface) {
            throw new RuntimeException("Class resolver does not implement ".FileNameResolverInterface::class);
        }

        return $resolverInstance;
    }

    /**
     * @param  callable<Request, bool, string>  $resolver
     */
    protected function resolveCallableResolver(callable $resolver): FileNameResolverInterface
    {
        return new Resolver($resolver);
    }

    /**
     * @see static::resolveClassResolver() on how the instances get loaded
     */
    public function forgetPreviousInstances(): void
    {
        //$this->container->forgetScopedInstances();

        // Unfortunately, using `app()->forgetScopedInstances();` does not work, so we simply remove previous instances manually
        // https://stackoverflow.com/questions/79424961/how-to-unset-scoped-instances-from-the-di-container-in-laravel
        foreach ($this->resolvedContainerResolvers as $resolver) {
            $this->container->offsetUnset($resolver);
        }
    }
}
