<?php

namespace HttpAutomock\Tests;

use HttpAutomock\HttpAutomockServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Workbench\App\Providers\WorkbenchServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HttpAutomockServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }
}
