<?php

namespace HttpAutomock\LaravelHttp;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as LaravelHttpFactory;

class Factory extends LaravelHttpFactory
{
    protected function newPendingRequest(): PendingRequest
    {
        return (new PendingRequest($this, $this->globalMiddleware))->withOptions(value($this->globalOptions));
    }
}
