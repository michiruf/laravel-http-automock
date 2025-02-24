<?php

namespace HttpAutomock\LaravelHttp;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as LaravelHttpFactory;

class Factory extends LaravelHttpFactory
{
    public function __construct(?Dispatcher $dispatcher = null)
    {
        parent::__construct($dispatcher);
    }

    protected function newPendingRequest(): PendingRequest
    {
        return (new PendingRequest($this, $this->globalMiddleware))->withOptions(value($this->globalOptions));
    }
}
