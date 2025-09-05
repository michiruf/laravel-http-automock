<?php

namespace HttpAutomock\LaravelHttp;

use HttpAutomock\Event\RealRequestSendingEvent;
use HttpAutomock\HttpAutomockServiceProvider;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\PendingRequest as LaravelPendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;

class PendingRequest extends LaravelPendingRequest
{
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
                // Construct the request like it is done int the stub handler
                $laravelRequest = (new Request($request))->withData($options['laravel_data']);

                HttpAutomockServiceProvider::getIndependentDispatcher()->dispatch(new RealRequestSendingEvent($laravelRequest));

                return $handler($request, $options);
            };
        };
    }

    protected function dispatchResponseReceivedEvent(Response $response): void
    {
        parent::dispatchResponseReceivedEvent($response);

        // Dispatch the event on the independent dispatcher for automock too
        HttpAutomockServiceProvider::getIndependentDispatcher()->dispatch(new ResponseReceived($this->request, $response));
    }
}
