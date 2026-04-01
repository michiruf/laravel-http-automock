<?php

namespace HttpAutomock;

use HttpAutomock\Support\HttpAutomockMixin;
use HttpAutomock\Support\RequestMixin;
use Illuminate\Http\Client\Request;
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
        Http::mixin(new HttpAutomockMixin);
        Request::mixin(new RequestMixin);
    }
}
