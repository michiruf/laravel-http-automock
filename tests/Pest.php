<?php

use HttpAutomock\Tests\TestCase;
use HttpAutomock\Tests\TestServer\TestServer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)->in(__DIR__);
uses(RefreshDatabase::class)->in(__DIR__.'/Unit');

// We want to start the test server once and shut it down after all tests
// Unfortunately, there might be no option to do this with packaged hook
function startOnce(): void
{
    // To ensure this is only done once using a static variable, the function must
    // be declared with a name rather than inside a closure
    static $hasRun = false;

    if (! $hasRun) {
        $hasRun = true;

        TestServer::start();

        register_shutdown_function(function () {
            TestServer::stop();
        });
    }
}

uses()
    ->beforeEach(fn () => startOnce())
    ->in(__DIR__);
