<?php

namespace HttpAutomock\Event;

use Illuminate\Http\Client\Request;

class RealRequestSendingEvent
{
    public function __construct(
        public Request $request,
    ) {}
}
