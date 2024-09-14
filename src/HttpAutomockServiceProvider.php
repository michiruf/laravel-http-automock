<?php

namespace HttpAutomock;

use HttpAutomock\Resolver\RequestFileNameResolver;
use HttpAutomock\Resolver\RequestFileNameResolverInterface;
use HttpAutomock\Support\HttpAutomockMixin;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/*
 * This class is a Package Service Provider
 *
 * More info: https://github.com/spatie/laravel-package-tools
 */

class HttpAutomockServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('http-automock')
            ->hasConfigFile();
    }

    public function bootingPackage(): void
    {
        $this->app->bind(RequestFileNameResolverInterface::class, RequestFileNameResolver::class);

        Http::mixin(new HttpAutomockMixin);
    }
}
