<?php

namespace HttpAutomock;

use HttpAutomock\Support\HttpAutomockMixin;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
