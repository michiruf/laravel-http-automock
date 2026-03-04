<?php

namespace HttpAutomock\LaravelHttp;

use Illuminate\Http\Client\Factory as LaravelHttpFactory;

class Factory extends LaravelHttpFactory
{
    protected function newPendingRequest(): PendingRequest
    {
        return (new PendingRequest($this, $this->globalMiddleware))->withOptions(value($this->globalOptions));
    }
}
