<?php

namespace HttpAutomock\Event;

use Psr\Http\Message\RequestInterface;

class RealRequestSendingEvent
{
    public function __construct(
        public RequestInterface $request,
    ) {}
}
