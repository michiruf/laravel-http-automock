<?php

namespace HttpAutomock;

use HttpAutomock\LaravelHttp\Factory;
use HttpAutomock\Support\HttpAutomockMixin;
use HttpAutomock\Support\RequestMixin;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\Factory as LaravelHttpFactory;
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
        /** @var LaravelHttpFactory $defaultHttpFactory */
        $defaultHttpFactory = Http::getFacadeRoot();
        Http::swap(new Factory($defaultHttpFactory->getDispatcher()));

        Http::mixin(new HttpAutomockMixin);
        Request::mixin(new RequestMixin);
    }

    public static function automockDispatcher(): \Illuminate\Contracts\Events\Dispatcher
    {
        app()->singletonIf('automock.dispatcher', fn (Container $container) => new Dispatcher($container));

        return app('automock.dispatcher');
    }
}
