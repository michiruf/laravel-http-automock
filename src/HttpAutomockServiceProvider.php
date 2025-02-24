<?php

namespace HttpAutomock;

use HttpAutomock\Support\HttpAutomockMixin;
use Illuminate\Http\Client\Factory;
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
        /** @var Factory $root */
        $root = Http::getFacadeRoot();
        Http::swap(new LaravelHttp\Factory($root->getDispatcher()));

        Http::mixin(new HttpAutomockMixin);
    }
}
