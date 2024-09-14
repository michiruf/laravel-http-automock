<?php

namespace HttpAutomock\Support;

use HttpAutomock\Facades\HttpAutomock as HttpAutomockFacade;
use HttpAutomock\HttpAutomock;

class HttpAutomockMixin
{
    /** @noinspection PhpUnused */
    public function automock(): callable
    {
        /**
         * Automatically mock requests.
         *
         * @return HttpAutomock
         */
        return function (): HttpAutomock {
            return HttpAutomockFacade::enable();
        };
    }

    /** @noinspection PhpUnused */
    public function noAutomock(): callable
    {
        /**
         * Disable automatically mocking requests.
         *
         * @return HttpAutomock
         */
        return function (): HttpAutomock {
            return HttpAutomockFacade::disable();
        };
    }
}
