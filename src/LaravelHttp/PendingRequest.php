<?php

namespace HttpAutomock\LaravelHttp;

use HttpAutomock\Event\RealRequestSendingEvent;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest as LaravelPendingRequest;

class PendingRequest extends LaravelPendingRequest
{
    public function __construct(?Factory $factory = null, $middleware = [])
    {
        parent::__construct($factory, $middleware);
    }

    public function pushHandlers($handlerStack)
    {
        // We want to register this event handler after laravels stub handler,
        // because from then on only real requests pipe through the handlers
        return tap(parent::pushHandlers($handlerStack), function ($stack) {
            $stack->push($this->buildRealRequestSendingHandler());
        });
    }

    public function buildRealRequestSendingHandler(): callable
    {
        return function ($handler) {
            return function ($request, $options) use ($handler) {
                event(new RealRequestSendingEvent($request));

                return $handler($request, $options);
            };
        };
    }
}
